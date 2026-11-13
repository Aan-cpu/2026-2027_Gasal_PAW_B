-- DATA CONTOH TAMBAHAN: NOVEMBER - DESEMBER 2026
-- Jalankan setelah penjualan.sql (tab SQL di phpMyAdmin, database penjualan).
-- Pelanggan PLG001 sampai PLG010 harus sudah ada (dibuat oleh penjualan.sql).
-- Kolom id tidak diisi, MySQL akan memberi nomor otomatis (AUTO_INCREMENT).

USE penjualan;

INSERT INTO transaksi (waktu_transaksi, keterangan, total, pelanggan_id) VALUES
-- Transaksi 1 sampai 13 November 2026 (sudah terjadi)
('2026-11-01', 'Belanja harian', 26000, 'PLG001'),
('2026-11-02', 'Belanja harian', 39000, 'PLG002'),
('2026-11-02', 'Belanja harian', 13000, 'PLG003'), -- tanggal sama dengan baris di atas, akan dijumlahkan oleh GROUP BY
('2026-11-03', 'Belanja harian', 52000, 'PLG004'),
('2026-11-05', 'Belanja harian', 13000, 'PLG001'), -- PLG001 belanja lagi, dihitung 1 orang oleh COUNT(DISTINCT)
('2026-11-06', 'Belanja harian', 26000, 'PLG005'),
('2026-11-08', 'Belanja harian', 19500, 'PLG006'),
('2026-11-09', 'Belanja harian', 39000, 'PLG007'),
('2026-11-10', 'Belanja harian', 13000, 'PLG002'),
('2026-11-12', 'Belanja harian', 26000, 'PLG008'),
('2026-11-13', 'Belanja harian', 32500, 'PLG009'),

-- Transaksi dengan tanggal ke depan (data uji)
('2026-11-16', 'Belanja harian', 26000, 'PLG003'),
('2026-11-18', 'Belanja harian', 13000, 'PLG010'),
('2026-11-20', 'Belanja harian', 45500, 'PLG004'),
('2026-11-25', 'Belanja harian', 39000, 'PLG005'),
('2026-11-30', 'Belanja harian', 26000, 'PLG001'),
('2026-12-02', 'Belanja harian', 13000, 'PLG006'),
('2026-12-05', 'Belanja harian', 52000, 'PLG007'),
('2026-12-10', 'Belanja harian', 26000, 'PLG008');
