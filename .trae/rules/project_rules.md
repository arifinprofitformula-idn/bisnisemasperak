---
alwaysApply: false
globs: 
---
"# User Rules — Bustanu (TRAE Preferences)

## Bahasa & Gaya Jawaban
- Bahasa utama: **Bahasa Indonesia**.
- Ringkas, to-the-point, disertai **langkah berurutan/checklist** saat memberi instruksi.
- Sertakan **contoh kode minimal yang bisa langsung jalan** (MVP) + komentar penting (ID/EN seperlunya).

## Konteks Teknis Default
- Hosting: cPanel/Apache, PHP **8.1** (kompatibel 7.4+), MySQL **5.7/8.0**.
- Basis referensi: **SimpleAff Plus** (SAP) — **jangan ubah core**. Ekstensi via **plugin/module/theme override**.
- Prefiks database khusus proyek: **`epi_`** untuk tabel/kolom baru.
- Timezone default tampilan: **Asia/Jakarta (WIB, UTC+7)**.

## Ekspektasi Output dari TRAE
- Saat memberi solusi teknis:
  1. Tampilkan **struktur folder** yang terdampak.
  2. Beri **potongan kode lengkap** (file path + isi) agar bisa ditempel.
  3. Sertakan **SQL migration** (CREATE/ALTER) dan **rollback** bila perlu.
  4. Tambahkan **.env keys** yang dibutuhkan (nama variabel + contoh nilai).
  5. Sertakan **uji cepat**: perintah/URL untuk verifikasi.
- Saat menyentuh fitur EPI Hub:
  - Gunakan **hook/action/filter** yang tersedia; bila tidak ada, gunakan **plugin custom** `plugins/epi/*`.
  - Jangan memodifikasi file tema/inti EPI Hub tanpa mencantumkan **override safe**.
- Saat membuat API/integrasi (Zoom, email autoresponder, WhatsApp):
  - Tampilkan **alur auth**, endpoint, contoh **request/response**, penyimpanan token (refresh), dan **fallback**.

## Keamanan Wajib
- Gunakan keamanan standar, simple, tidak terlalu ketat tapi sangat aman.
- Gunakan **prepared statements**/ORM, **CSRF** token, **XSS escaping**, **rate limit** pada endpoint publik.
- Masking data sensitif saat logging. Jangan tampilkan kunci/secret asli.

## Versi & Git
- Konvensi commit: `feat(scope): deskripsi`, `fix`, `chore`, `refactor`, `docs`.
- Cabang kerja: `feature/<slug>`, `hotfix/<slug>`.
- Sertakan snippet **git** dan **deploy** bila perubahan menyentuh banyak file.

## UX/UI
- Desain mobile-first, aksesibilitas (kontras/aria), loading state, dan **empty state** jelas.
- Komponen UI reusable; hindari inline style berlebihan.

## Gaya Penamaan
- Tabel/kolom: `epi_<domain>`, snake_case.
- PHP/JS: camelCase untuk variabel/fungsi, PascalCase untuk class.
- Route/slug publik stabil dan SEO-friendly.

## Hal yang Diutamakan
- **Backward compatible** dengan instalasi SAP.
- **Kinerja**: cache query berat, paginasi, gambar terkompresi.
- **Observabilitas**: log ringkas + ID korelasi (request_id).
- **Selalu berikan langkah-langkah untuk update di Cpanel ketika ada mengupdate database localhost**"