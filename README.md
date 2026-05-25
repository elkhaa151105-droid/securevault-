# SecureVault

SecureVault adalah aplikasi web berbasis PHP untuk penyimpanan file terenkripsi dengan fitur upload, download, sharing file, dan manajemen user. Sistem ini menerapkan enkripsi hybrid menggunakan AES-256-GCM dan RSA-2048 untuk menjaga keamanan file pengguna.

---

## Fitur Utama

-  Enkripsi file menggunakan AES-256-GCM
-  RSA-2048 key pair untuk setiap user
-  Private key terenkripsi dengan password user
-  Upload file terenkripsi
-  Download & dekripsi file
-  Berbagi file antar user
-  Hapus file
-  Logging aktivitas user
-  Panel admin
-  Session authentication
-  Penyimpanan metadata file di database

---

##  Teknologi yang Digunakan

- PHP Native
- MySQL / MariaDB
- OpenSSL
- HTML, CSS, JavaScript
- AES-256-GCM
- RSA-2048
- PBKDF2

---

##  Struktur Folder

```bash
securevault/
│
├── admin/              # Panel admin
├── auth/               # Login, register, logout
├── config/             # Konfigurasi database
├── file/               # Upload, download, preview, delete
├── includes/           # Session & crypto helper
├── keys/               # Penyimpanan key
├── share/              # Sistem sharing file
├── uploads/            # File terenkripsi
├── securevault.sql     # Database schema
├── index.php           # Dashboard utama
└── 403.php             # Forbidden page
```

---

##  Mekanisme Keamanan

### 1. Enkripsi File

Setiap file dienkripsi menggunakan:

- AES-256-GCM
- Session key acak 32 byte
- IV random
- Authentication tag untuk integritas data

### 2. Hybrid Encryption

Session key AES dienkripsi kembali menggunakan:

- RSA-2048 Public Key user
- OAEP Padding

### 3. Proteksi Private Key

Private key user:

- Dienkripsi menggunakan AES-256-CBC
- Derivasi key memakai PBKDF2
- Salt & IV random

---

##  Instalasi

### 1. Clone / Extract Project

Pindahkan project ke folder web server:

```bash
htdocs/securevault
```

Jika menggunakan XAMPP:

```bash
C:/xampp/htdocs/securevault
```

---

### 2. Import Database

Buat database baru:

```sql
CREATE DATABASE securevault;
```

Import file:

```bash
securevault.sql
```

---

### 3. Konfigurasi Database

Edit file:

```bash
config/db.php
```

Contoh konfigurasi:

```php
$host = 'localhost';
$db   = 'securevault';
$user = 'root';
$pass = '';
```

---

### 4. Jalankan Apache & MySQL

Gunakan:

- XAMPP
- Laragon
- Apache + MySQL manual

Lalu buka:

```bash
http://localhost/securevault
```

---

##  Default Role

Sistem mendukung:

- User biasa
- Admin

Admin dapat:

- Melihat data user
- Menghapus user
- Mengelola sistem

---

##  Alur Upload File

1. User memilih file
2. Sistem membuat session key AES
3. File dienkripsi AES-256-GCM
4. Session key dienkripsi RSA public key user
5. File terenkripsi disimpan di server
6. Metadata disimpan di database

---

##  Alur Sharing File

1. Pemilik file memilih user target
2. Session key didekripsi memakai private key pengirim
3. Session key dienkripsi ulang dengan public key penerima
4. Data sharing disimpan ke tabel `file_shares`

---

##  Database

Beberapa tabel utama:

| Tabel          | Fungsi                    |
| -------------- | ------------------------- |
| users          | Menyimpan data user       |
| files          | Metadata file terenkripsi |
| file\_shares   | Data sharing file         |
| activity\_logs | Logging aktivitas         |

---

##  Fungsi Cryptography

File:

```bash
includes/crypto.php
```

Berisi fungsi:

- `generateKeyPair()`
- `encryptPrivateKey()`
- `decryptPrivateKey()`
- `encryptSessionKey()`
- `decryptSessionKey()`
- `encryptFile()`
- `decryptFile()`

---

##  Catatan Keamanan

- Folder uploads hanya menyimpan file terenkripsi
- Nama file diacak menggunakan `random_bytes()`
- Session key tidak disimpan dalam bentuk plaintext
- Private key user tidak pernah disimpan tanpa enkripsi
- Menggunakan authentication tag untuk mencegah manipulasi data

---

##  Pengembangan Selanjutnya

Fitur yang bisa ditambahkan:

- Two-Factor Authentication (2FA)
- Email verification
- Expired share link
- Drag & drop upload
- File versioning
- Audit log lebih detail
- Encrypt folder
- Cloud storage integration

---

##  Tampilan Sistem

Halaman utama:

- Login
- Register
- Dashboard file
- Upload area
- Sharing panel
- Admin panel

---

##  Kebutuhan Sistem

Minimal:

- PHP 8+
- OpenSSL Extension
- MySQL / MariaDB
- Apache / Nginx

---

##  License

Project ini dibuat untuk kebutuhan pembelajaran, penelitian, atau pengembangan sistem keamanan file berbasis web.

---

##  Demo Video

Link YouTube:

```text
https://youtube.com/
```

Link YT menyusul karena masih dalam tahap edit video

---

##  Author

Dibuat untuk memenuhi Tugas Akhir Mata Kuliah Kriptografi.
