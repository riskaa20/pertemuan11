<?php

namespace App\Filament\Pages;

use App\Models\Barang;
use Filament\Pages\Page;
use App\Models\Pembelian;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;


class PembelianPage extends Page implements HasTable, Hasforms
{
    use InteractsWithForms, InteractsWithTable;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.pembelian-page';

    public static function getNavigationLabel(): string
    {
        return 'Halaman Pembelian';
    }


    protected function getTableQuery(): Builder
    {
        return Pembelian::query()->with('barang')->latest();
    }
      protected function getHeaderActions(): array
  {
    return [
      Action::make('create')
        ->label('Tambah Pembelian')
        ->icon('heroicon-o-plus')
        ->color('primary')
        ->action(function (array $data) {
          // Menggunakan transaksi database untuk memastikan integritas data
          try {
            DB::transaction(function () use ($data) {
              // 1. Simpan data pembelian baru
              Pembelian::create($data);

              // 2. Cari barang yang sesuai
              $barang = Barang::find($data['barang_id']);
              // 3. Tambahkan jumlah pembelian ke stok barang
              if ($barang) {
                // Menggunakan increment untuk operasi yang aman
                $barang->increment('stok', $data['jumlah']);
              }
            });

            // Menampilkan notifikasi sukses
            Notification::make()
              ->title('Pembelian berhasil! Stok barang telah diupdate.')
              ->success()
              ->send();
          } catch (\Exception $e) {
            // Menampilkan notifikasi jika terjadi error
            Notification::make()
              ->title('Gagal menyimpan pembelian')
              ->body($e->getMessage()) // Bisa dihapus di production
              ->danger()
              ->send();
          }
        })
        ->form([
          // Form untuk input data pembelian
          Select::make('barang_id')
            ->label('Nama Barang')
            ->options(Barang::query()->pluck('nama', 'id'))
            ->searchable()
            ->required(),
          TextInput::make('nama_pemasok')
            ->label('Nama Pemasok')
            ->required()
            ->maxLength(255),
          TextInput::make('jumlah')
            ->label('Jumlah')
            ->required()
            ->numeric()
            ->minValue(1),
        ])
        ->modalHeading('Tambah Data Pembelian Baru')
        ->modalButton('Simpan'),
    ];
  }

  protected function getTableColumns(): array
  {
    return [
      // Menampilkan nama barang dari relasi
      TextColumn::make('barang.nama')
        ->label('Nama Barang')
        ->searchable()
        ->sortable(),

      // Menampilkan stok barang saat ini
      TextColumn::make('barang.stok')
        ->label('Stok Saat Ini')
        ->numeric()
        ->sortable(),

      TextColumn::make('nama_pemasok')
        ->label('Nama Pemasok')
        ->searchable(),

      TextColumn::make('jumlah')
        ->label('Jumlah Dibeli')
        ->sortable(),

      TextColumn::make('created_at')
        ->label('Tanggal Pembelian')
        ->dateTime('d M Y, H:i')
        ->sortable(),
    ];
  }
    protected function getTableActions(): array
  {
    return [
      DeleteAction::make()
        ->requiresConfirmation()
        ->action(function (Pembelian $record) {
          try {
            DB::transaction(function () use ($record) {
              // 1. Cari barang yang terkait dengan record pembelian
              $barang = Barang::find($record->barang_id);

              // 2. Kurangi stok barang sejumlah yang ada di record pembelian
              if ($barang) {
                $barang->decrement('stok', $record->jumlah);
              }

              // 3. Hapus record pembelian itu sendiri
              $record->delete();
            });

            Notification::make()
              ->title('Pembelian berhasil dihapus!')
              ->body('Stok barang telah dikembalikan ke jumlah sebelumnya.')
              ->success()
              ->send();
          } catch (\Exception $e) {
            Notification::make()
              ->title('Gagal menghapus pembelian')
              ->body($e->getMessage())
              ->danger()
              ->send();
          }
        }),
    ];
  }

}
