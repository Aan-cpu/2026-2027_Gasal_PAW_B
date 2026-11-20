/* Database penjualan untuk Modul 8 (Session dan Security).
   DROP DATABASE dipakai supaya file ini bisa diimport berulang kali tanpa error data kembar. */
DROP DATABASE IF EXISTS penjualan;
CREATE DATABASE penjualan CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE penjualan;

CREATE TABLE supplier (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  telp VARCHAR(15),
  alamat TEXT
);

CREATE TABLE barang (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode_barang VARCHAR(10) NOT NULL UNIQUE,
  nama_barang VARCHAR(100) NOT NULL,
  harga INT NOT NULL,
  stok INT NOT NULL,
  supplier_id INT,
  FOREIGN KEY (supplier_id) REFERENCES supplier(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE pelanggan (
  id VARCHAR(20) PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  jenis_kelamin ENUM('L','P'),
  telp VARCHAR(15),
  alamat TEXT
);

CREATE TABLE transaksi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  waktu_transaksi DATE,
  keterangan TEXT,
  total INT DEFAULT 0,
  pelanggan_id VARCHAR(20),
  FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE transaksi_detail (
  transaksi_id INT NOT NULL,
  barang_id INT NOT NULL,
  harga INT NOT NULL,
  qty INT NOT NULL,
  PRIMARY KEY (transaksi_id, barang_id),
  FOREIGN KEY (transaksi_id) REFERENCES transaksi(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (barang_id) REFERENCES barang(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE pembayaran (
  id INT AUTO_INCREMENT PRIMARY KEY,
  waktu_bayar DATETIME,
  total INT,
  metode ENUM('TUNAI','TRANSFER','EDC'),
  transaksi_id INT,
  FOREIGN KEY (transaksi_id) REFERENCES transaksi(id)
    ON DELETE CASCADE ON UPDATE CASCADE
);

/* Akun login. level 1 = owner, level 2 = kasir.
   Kolom password 255 karakter karena hasil password_hash() panjang (sebelumnya 35 untuk MD5). */
CREATE TABLE `user` (
  id_user INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  nama VARCHAR(50),
  alamat VARCHAR(150),
  hp VARCHAR(20),
  level TINYINT NOT NULL
);

INSERT INTO supplier (id, nama, telp, alamat) VALUES
(1,'PT Sumber Makmur','03155510101','Surabaya'),
(2,'CV Tani Jaya','03155510102','Sidoarjo'),
(3,'UD Berkah Abadi','03155510103','Bangkalan'),
(4,'PT Nusantara Pangan','03155510104','Malang'),
(5,'CV Maju Bersama','03155510105','Gresik');

INSERT INTO barang (id, kode_barang, nama_barang, harga, stok, supplier_id) VALUES
(1,'BRG001','Beras 5kg',65000,50,1),
(2,'BRG002','Minyak Goreng 2L',38000,40,1),
(3,'BRG003','Gula Pasir 1kg',17000,60,2),
(4,'BRG004','Telur Ayam 1kg',29000,30,3),
(5,'BRG005','Kopi Bubuk 200g',22000,45,4),
(6,'BRG006','Teh Celup 25s',9000,80,5),
(7,'BRG007','Susu UHT 1L',18000,70,2);

INSERT INTO pelanggan (id, nama, jenis_kelamin, telp, alamat) VALUES
('PLG001','Budi Santoso','L','081234500001','Bangkalan'),
('PLG002','Siti Aminah','P','081234500002','Surabaya'),
('PLG003','Ahmad Fauzi','L','081234500003','Sampang'),
('PLG004','Dewi Lestari','P','081234500004','Pamekasan'),
('PLG005','Rizki Pratama','L','081234500005','Sumenep');

/* Password sudah di-hash (bcrypt):
   admin / admin123, owner / owner123, kasir1 dan kasir2 / kasir123, gudang1 / gudang123 */
INSERT INTO `user` (username, password, nama, alamat, hp, level) VALUES
('admin','$2y$10$XuMlYQeFG6bwv8KBL49P2OidJVY/KFWrs2dd2uJJw2J5VCoBeW3ee','Administrator','Bangkalan','081200000001',1),
('kasir1','$2y$10$0m3psJBwNjG6HQstTDOLs.k9rkPLdYDJwYKW0zO27BVGNLZ946fxO','Kasir Satu','Bangkalan','081200000002',2),
('kasir2','$2y$10$OBlA/sUBDzxGdZxHl4WXBOVw7KCkeJ1ad9Fol4B/Or/0GTWqFhyz2','Kasir Dua','Surabaya','081200000003',2),
('gudang1','$2y$10$bVYAuGXp8.uSmayLkiK/IeCj7eunw0KV.tyBQAHYgp7nbdL3Ewt8e','Staf Gudang','Sidoarjo','081200000004',2),
('owner','$2y$10$D.8u4URA662miiI0LhIPiOsp5huje47egvxONg.mD/ZAWBk997Cy6','Pemilik Toko','Malang','081200000005',1);

/* Data transaksi: 10 transaksi, kolom total diisi dari detail lewat UPDATE di bawah */
INSERT INTO transaksi (id, waktu_transaksi, keterangan, total, pelanggan_id) VALUES
(1,'2026-10-01','Belanja bulanan',0,'PLG001'),
(2,'2026-10-03','Belanja dapur',0,'PLG002'),
(3,'2026-10-05','Beli telur',0,'PLG003'),
(4,'2026-10-08','Belanja mingguan',0,'PLG004'),
(5,'2026-10-12','Belanja dapur',0,'PLG005'),
(6,'2026-10-15','Beli gula',0,'PLG001'),
(7,'2026-10-20','Belanja warung',0,'PLG002'),
(8,'2026-10-25','Belanja mingguan',0,'PLG003'),
(9,'2026-11-01','Beli beras',0,'PLG004'),
(10,'2026-11-04','Belanja bulanan',0,'PLG005');

INSERT INTO transaksi_detail (transaksi_id, barang_id, harga, qty) VALUES
(1,1,65000,2),(1,3,17000,1),
(2,2,38000,1),(2,6,9000,3),
(3,4,29000,2),
(4,5,22000,1),(4,7,18000,2),(4,6,9000,1),
(5,1,65000,1),(5,2,38000,2),
(6,3,17000,5),
(7,7,18000,3),(7,5,22000,2),
(8,4,29000,1),(8,6,9000,4),(8,3,17000,2),
(9,1,65000,3),
(10,2,38000,2),(10,5,22000,1),(10,7,18000,1);

UPDATE transaksi t SET total =
  (SELECT SUM(d.harga * d.qty) FROM transaksi_detail d WHERE d.transaksi_id = t.id);

INSERT INTO pembayaran (waktu_bayar, total, metode, transaksi_id) VALUES
('2026-10-01 10:15:00',147000,'TUNAI',1),
('2026-10-03 14:30:00',65000,'TRANSFER',2),
('2026-10-05 09:45:00',58000,'EDC',3);
