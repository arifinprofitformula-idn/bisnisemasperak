-- Perubahan tipe data art_product untuk mendukung multiple selection
-- Jalankan perintah ini di database Anda (phpMyAdmin atau CLI)

ALTER TABLE `sa_artikel` MODIFY `art_product` TEXT DEFAULT NULL;
