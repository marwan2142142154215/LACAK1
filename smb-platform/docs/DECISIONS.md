# DECISIONS

## 1. Struktur repo
Monorepo `smb-platform/` berisi komponen terpisah sesuai spec section 60.

## 2. Environment lokal saat ini
- Node.js v24 tersedia.
- PHP, Composer, Java, PostgreSQL, Redis, .NET belum terinstal di mesin ini.
- Build/test fase-fase berikut akan berjalan setelah toolchain dipasang (lihat deployment.md). Struktur kode tetap dibuat lengkap dan nyata.

## 3. WebSocket authentication
Device tidak boleh auth hanya dengan device_id. Gateway akan memvalidasi device credential (HMAC/token) terhadap Laravel API sebelum menerima koneksi.

## 4. Command delivery
Fallback HTTPS polling disediakan ketika WSS gagal. Command yang expired tidak dieksekusi. Idempotency key + command_id unique constraint mencegah duplicate execution.

## 5. Camera & Location
Capability detection jujur: jika Android membatasi background camera/location, status FAILED dengan reason yang jelas. Tidak ada klaim false-positive.

## 6. Device Owner
Fitur lock task mode hanya aktif jika app memang Device Owner. Jika tidak, ditampilkan "Fitur membutuhkan perangkat terkelola."
