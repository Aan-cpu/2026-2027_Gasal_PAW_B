-- Database : data_penjualan
-- Modul 6 PAW - Master Detil
-- Hapus database lama jika sudah ada (supaya import bisa diulang)
DROP DATABASE IF EXISTS data_penjualan;
-- Buat database baru dengan karakter UTF-8
CREATE DATABASE data_penjualan CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- Pilih database yang akan dipakai
USE data_penjualan;

-- ---------- TABEL ----------
-- Tabel supplier (MASTER): pemasok barang
CREATE TABLE supplier (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  nama   VARCHAR(100) NOT NULL,
  telp   VARCHAR(12),
  alamat TEXT
) ENGINE=InnoDB;

-- Tabel barang (MASTER): supplier_id adalah foreign key ke supplier
-- ON DELETE RESTRICT : supplier tidak bisa dihapus jika masih punya barang
CREATE TABLE barang (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  kode_barang VARCHAR(10) NOT NULL UNIQUE,
  nama_barang VARCHAR(100) NOT NULL,
  harga       INT NOT NULL,
  stok        INT NOT NULL DEFAULT 0,
  supplier_id INT NOT NULL,
  CONSTRAINT fk_barang_supplier FOREIGN KEY (supplier_id)
    REFERENCES supplier(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Tabel pelanggan (MASTER): id bertipe varchar(20), contoh PLG001
CREATE TABLE pelanggan (
  id            VARCHAR(20) PRIMARY KEY,
  nama          VARCHAR(100) NOT NULL,
  jenis_kelamin ENUM('L','P') NOT NULL,
  telp          VARCHAR(12),
  alamat        TEXT
) ENGINE=InnoDB;

-- Tabel transaksi (MASTER dari transaksi_detail)
-- total diisi otomatis oleh program saat detail ditambahkan (default 0)
CREATE TABLE transaksi (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  waktu_transaksi  DATE NOT NULL,
  keterangan       TEXT,
  total            INT NOT NULL DEFAULT 0,
  pelanggan_id     VARCHAR(20) NOT NULL,
  CONSTRAINT fk_transaksi_pelanggan FOREIGN KEY (pelanggan_id)
    REFERENCES pelanggan(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Tabel transaksi_detail (DETAIL) sekaligus tabel perantara (junction table)
-- antara transaksi dan barang (relasi many-to-many)
-- Composite PK (transaksi_id, barang_id): satu barang hanya boleh sekali per transaksi
-- harga disimpan sendiri sebagai catatan harga saat transaksi terjadi
-- Transaksi dihapus -> detail ikut terhapus (CASCADE)
-- Barang yang sudah dipakai tidak bisa dihapus (RESTRICT)
CREATE TABLE transaksi_detail (
  transaksi_id INT NOT NULL,
  barang_id    INT NOT NULL,
  harga        INT NOT NULL,
  qty          INT NOT NULL,
  PRIMARY KEY (transaksi_id, barang_id),
  CONSTRAINT fk_detail_transaksi FOREIGN KEY (transaksi_id)
    REFERENCES transaksi(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_detail_barang FOREIGN KEY (barang_id)
    REFERENCES barang(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Tabel pembayaran: pembayaran milik satu transaksi
CREATE TABLE pembayaran (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  waktu_bayar  DATETIME NOT NULL,
  total        INT NOT NULL,
  metode       ENUM('TUNAI','TRANSFER','EDC') NOT NULL,
  transaksi_id INT NOT NULL,
  CONSTRAINT fk_bayar_transaksi FOREIGN KEY (transaksi_id)
    REFERENCES transaksi(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Tabel user: untuk login, berdiri sendiri (tanpa relasi)
CREATE TABLE user (
  id_user  TINYINT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL UNIQUE,
  password VARCHAR(35) NOT NULL,
  nama     VARCHAR(50),
  alamat   VARCHAR(150),
  hp       VARCHAR(20),
  level    TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------- DATA MASTER ----------
INSERT INTO supplier (nama, telp, alamat) VALUES
('PT Sumber Pangan',  '031555101', 'Jl. Raya Surabaya No. 10, Surabaya'),
('CV Maju Jaya',      '031555102', 'Jl. Pahlawan No. 25, Sidoarjo'),
('UD Berkah Abadi',   '031555103', 'Jl. Diponegoro No. 7, Gresik'),
('PT Indo Distribusi','031555104', 'Jl. Ahmad Yani No. 88, Surabaya'),
('CV Sinar Terang',   '031555105', 'Jl. Merdeka No. 3, Bangkalan');

INSERT INTO pelanggan (id, nama, jenis_kelamin, telp, alamat) VALUES
('PLG001','Ahmad Fauzi',   'L','081234567801','Jl. Mawar No. 1, Bangkalan'),
('PLG002','Siti Aminah',   'P','081234567802','Jl. Melati No. 2, Bangkalan'),
('PLG003','Budi Santoso',  'L','081234567803','Jl. Kenanga No. 3, Sampang'),
('PLG004','Dewi Lestari',  'P','081234567804','Jl. Anggrek No. 4, Pamekasan'),
('PLG005','Rizky Ramadhan','L','081234567805','Jl. Dahlia No. 5, Sumenep'),
('PLG006','Nur Hidayah',   'P','081234567806','Jl. Cempaka No. 6, Bangkalan');

INSERT INTO barang (kode_barang, nama_barang, harga, stok, supplier_id) VALUES
('BRG001','Beras 5 Kg',        62000, 50, 1),
('BRG002','Minyak Goreng 2 L', 38000, 40, 1),
('BRG003','Gula Pasir 1 Kg',   17000, 60, 2),
('BRG004','Kopi Bubuk 200 g',  25000, 35, 2),
('BRG005','Teh Celup 25 pcs',   9000, 80, 3),
('BRG006','Susu UHT 1 L',      18000, 45, 3),
('BRG007','Mie Instan',         3500,200, 4),
('BRG008','Sabun Mandi',        6000,100, 5),
-- barang ini sengaja tidak dipakai di transaksi (untuk uji hapus barang)
('BRG009','Pasta Gigi',        12000, 70, 5);

-- ---------- DATA TRANSAKSI (10) ----------
INSERT INTO transaksi (waktu_transaksi, keterangan, pelanggan_id) VALUES
('2026-10-01','Belanja bulanan',        'PLG001'),
('2026-10-03','Pembelian kebutuhan dapur','PLG002'),
('2026-10-05','Stok warung',            'PLG003'),
('2026-10-08','Belanja mingguan',       'PLG004'),
('2026-10-10','Pembelian kopi dan teh', 'PLG005'),
('2026-10-14','Belanja bulanan',        'PLG006'),
('2026-10-18','Kebutuhan harian',       'PLG001'),
('2026-10-22','Stok warung',            'PLG003'),
('2026-10-27','Belanja mingguan',       'PLG002'),
('2026-11-02','Pembelian sembako',      'PLG004');

-- Isi detail transaksi (10 transaksi). Kolom: nomor transaksi, id barang, qty
-- harga diambil otomatis dari tabel barang lewat JOIN
-- harga diambil dari tabel barang (snapshot harga saat transaksi)
INSERT INTO transaksi_detail (transaksi_id, barang_id, harga, qty)
SELECT v.t, b.id, b.harga, v.q FROM (
  SELECT 1 t,1 bid,2 q UNION ALL SELECT 1,3,3 UNION ALL SELECT 1,5,2 UNION ALL
  SELECT 2,2,2 UNION ALL SELECT 2,3,1 UNION ALL
  SELECT 3,7,50 UNION ALL SELECT 3,8,10 UNION ALL SELECT 3,6,6 UNION ALL
  SELECT 4,1,1 UNION ALL SELECT 4,4,2 UNION ALL
  SELECT 5,4,3 UNION ALL SELECT 5,5,5 UNION ALL
  SELECT 6,1,2 UNION ALL SELECT 6,2,1 UNION ALL SELECT 6,6,4 UNION ALL
  SELECT 7,7,20 UNION ALL SELECT 7,3,2 UNION ALL
  SELECT 8,8,24 UNION ALL SELECT 8,7,40 UNION ALL
  SELECT 9,2,3 UNION ALL SELECT 9,4,1 UNION ALL
  SELECT 10,1,3 UNION ALL SELECT 10,3,5 UNION ALL SELECT 10,6,2
) v JOIN barang b ON b.id = v.bid;

-- Hitung total tiap transaksi = jumlah (harga x qty) dari semua detailnya
UPDATE transaksi t SET total = (
  SELECT COALESCE(SUM(harga*qty),0) FROM transaksi_detail d WHERE d.transaksi_id = t.id);

-- Isi pembayaran untuk 8 transaksi pertama (total sama dengan total transaksi,
-- metode bergantian TUNAI, TRANSFER, EDC)
INSERT INTO pembayaran (waktu_bayar, total, metode, transaksi_id)
SELECT CONCAT(waktu_transaksi,' 10:00:00'), total,
       ELT(1 + (id % 3), 'TUNAI','TRANSFER','EDC'), id
FROM transaksi WHERE id <= 8;

INSERT INTO user (username, password, nama, alamat, hp, level) VALUES
('admin', MD5('admin123'), 'Administrator', 'Bangkalan', '081200000001', 1),
('kasir', MD5('kasir123'), 'Kasir Toko',    'Bangkalan', '081200000002', 2);
