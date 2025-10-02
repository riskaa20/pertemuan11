<?php

namespace App\Filament\Pages;

use App\Models\Barang;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use Filament\Actions\Action;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;


class PenjualanPage extends Page implements HasTable, HasForms
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.penjualan-page';

    public static function getNavigationLabel(): string
    {
        return 'Halaman Penjualan';
    }

    protected function getTableQuery(): Builder
    {
        return Penjualan::query()
            ->with(
                'pelanggan',
                'penjualanDetails.barang'
            )->latest();
    }

      protected function getHeaderActions(): array
  {
    return [
      Action::make('create')
        ->label('Tambah Penjualan')
        ->icon('heroicon-o-plus')
        ->color('primary')
        ->action(function (array $data) {
          try {
            DB::transaction(function () use ($data) {
              // 1. Buat record Penjualan utama
              $penjualan = Penjualan::create([
                'pelanggan_id' => $data['pelanggan_id'],
                'keterangan' => $data['keterangan'],
              ]);

              // 2. Loop melalui setiap item barang yang dijual
              foreach ($data['items'] as $item) {
                $barang = Barang::find($item['barang_id']);

                // Validasi stok barang
                if ($barang->stok < $item['jumlah']) {
                  // Melemparkan exception untuk membatalkan transaksi
                  throw new \Exception("Stok untuk barang '{$barang->nama}' tidak mencukupi. Sisa stok: {$barang->stok}.");
                }

                // 3. Buat record PenjualanDetail
                PenjualanDetail::create([
                  'penjualan_id' => $penjualan->id,
                  'barang_id' => $item['barang_id'],
                  'jumlah' => $item['jumlah'],
                  'harga' => $item['harga'],
                ]);

                // 4. Kurangi stok barang
                $barang->decrement('stok', $item['jumlah']);
              }
            });

            Notification::make()
              ->title('Penjualan berhasil!')
              ->body('Stok barang telah diupdate.')
              ->success()
              ->send();
          } catch (\Exception $e) {
            Notification::make()
              ->title('Gagal menyimpan penjualan')
              ->body($e->getMessage())
              ->danger()
              ->send();
          }
        })
        ->form([
          Select::make('pelanggan_id')
            ->label('Nama Pelanggan')
            ->options(Pelanggan::query()->pluck('nama', 'id')) // Asumsi ada model Pelanggan
            ->searchable()
            ->required(),
          Textarea::make('keterangan')
            ->label('Keterangan')
            ->required(),
          Repeater::make('items')
            ->label('Detail Barang')
            ->schema([
              Select::make('barang_id')
                ->label('Nama Barang')
                ->options(Barang::query()->pluck('nama', 'id'))
                ->searchable()
                ->required()
                ->reactive()
                ->afterStateUpdated(fn($state, callable $set) => $set('harga', Barang::find($state)?->harga ?? 0)), // Asumsi ada field harga_jual di model Barang
              TextInput::make('jumlah')
                ->label('Jumlah')
                ->numeric()
                ->required()
                ->minValue(1)
                ->default(1),
              TextInput::make('harga')
                ->label('Harga Satuan')
                ->numeric()
                ->required()
                ->prefix('Rp'),
            ])
            ->columns(3)
            ->required(),
        ])
        ->modalHeading('Tambah Data Penjualan Baru')
        ->modalButton('Simpan'),
    ];
  }

    protected function getTableColumns(): array
  {
    return [
      TextColumn::make('pelanggan.nama')
        ->label('Nama Pelanggan')
        ->searchable()
        ->sortable(),
      TextColumn::make('keterangan')
        ->label('Keterangan')
        ->searchable(),
      TextColumn::make('total_harga')
        ->label('Total Harga')
        ->numeric()
        ->sortable()
        ->state(function (Penjualan $record) {
          // Menghitung total harga dari detail penjualan
          return $record->penjualanDetails->sum(function ($detail) {
            return $detail->harga * $detail->jumlah;
          });
        })
        ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.')),
      TextColumn::make('created_at')
        ->label('Tanggal Penjualan')
        ->dateTime('d M Y, H:i')
        ->sortable(),
    ];
  }

    protected function getTableActions(): array
  {
    return [
      TableAction::make('detail')
        ->label('Detail')
        ->icon('heroicon-o-eye')
        ->color('gray')
        ->modalHeading('Detail Penjualan')
        ->modalSubmitAction(false) // Tidak ada tombol submit
        ->modalCancelActionLabel('Tutup')
        ->form([
          TextInput::make('pelanggan_nama')
            ->label('Nama Pelanggan')
            ->disabled(),
          TextInput::make('tanggal_penjualan')
            ->label('Tanggal Penjualan')
            ->disabled(),
          Textarea::make('keterangan')
            ->label('Keterangan')
            ->disabled(),
          // Repeater untuk menampilkan detail item
          Repeater::make('penjualanDetails')
            ->label('Detail Barang')
            ->schema([
              TextInput::make('barang_nama')->label('Nama Barang')->disabled(),
              TextInput::make('jumlah')->label('Jumlah')->disabled(),
              TextInput::make('harga')->label('Harga Satuan')->disabled()->prefix('Rp'),
              TextInput::make('subtotal')->label('Subtotal')->disabled()->prefix('Rp'),
            ])
            ->disabled() // Menonaktifkan repeater agar tidak bisa diubah
            ->columns(4)
            ->dehydrated(false)
            ->reorderable(false)
        ])
        ->mountUsing(function ($form, Penjualan $record) {
          // Mempersiapkan data untuk ditampilkan di form
          $details = $record->penjualanDetails->map(function ($detail) {
            return [
              'barang_nama' => $detail->barang->nama,
              'jumlah' => $detail->jumlah,
              'harga' => number_format($detail->harga, 0, ',', '.'),
              'subtotal' => number_format($detail->harga * $detail->jumlah, 0, ',', '.'),
            ];
          });

          $form->fill([
            'pelanggan_nama' => $record->pelanggan->nama,
            'tanggal_penjualan' => $record->created_at->translatedFormat('d F Y, H:i'),
            'keterangan' => $record->keterangan,
            'penjualanDetails' => $details->all(),
          ]);
        }),
      DeleteAction::make()
        ->requiresConfirmation()
        ->action(function (Penjualan $record) {
          try {
            DB::transaction(function () use ($record) {
              // 1. Kembalikan stok untuk setiap item dalam penjualan
              foreach ($record->penjualanDetails as $detail) {
                $barang = $detail->barang;
                if ($barang) {
                  $barang->increment('stok', $detail->jumlah);
                }
              }

              // 2. Hapus record penjualan (detail akan terhapus otomatis jika ada foreign key constraint on-delete cascade)
              $record->delete();
            });

            Notification::make()
              ->title('Penjualan berhasil dihapus!')
              ->body('Stok barang telah dikembalikan.')
              ->success()
              ->send();
          } catch (\Exception $e) {
            Notification::make()
              ->title('Gagal menghapus penjualan')
              ->body($e->getMessage())
              ->danger()
              ->send();
          }
        }),
    ];
  }
}
