-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 24 Bulan Mei 2026 pada 08.39
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `securevault`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `file_id` int(11) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `file_id`, `file_name`, `ip_address`, `created_at`) VALUES
(1, 2, 'login', NULL, NULL, '::1', '2026-05-23 14:01:06'),
(2, 2, 'login', NULL, NULL, '::1', '2026-05-23 14:01:20'),
(3, 2, 'upload', 2, 'Screenshot (63).png', '::1', '2026-05-23 14:17:09'),
(4, 2, 'share', 2, 'Screenshot (63).png', '::1', '2026-05-23 14:17:27'),
(5, 1, 'login', NULL, NULL, '::1', '2026-05-23 14:17:57'),
(6, 2, 'login', NULL, NULL, '::1', '2026-05-23 15:46:51'),
(7, 1, 'login', NULL, NULL, '::1', '2026-05-24 03:56:27'),
(8, 2, 'login', NULL, NULL, '::1', '2026-05-24 06:28:04');

-- --------------------------------------------------------

--
-- Struktur dari tabel `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `encrypted_key` text NOT NULL,
  `iv` varchar(255) NOT NULL,
  `auth_tag` varchar(255) NOT NULL,
  `file_hash` varchar(64) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `files`
--

INSERT INTO `files` (`id`, `owner_id`, `original_name`, `stored_name`, `file_size`, `encrypted_key`, `iv`, `auth_tag`, `file_hash`, `uploaded_at`) VALUES
(1, 1, 'WhatsApp Image 2026-05-13 at 15.03.29.jpeg', 'e7b618f7ecbef9a24312917a5abba869.enc', 59556, 'A2rnpyOhQ7JorE5aNjH372Gn7wGAMuebd8eHZBDqZE5pIuzt9FSmi1W8W2u6BDYY8KA77LsbCL80JNYRelRPx070hw/JaeTLW4VepgMRnosN29UjNGhZ/cN14ohtXeta3DZRpnXJswP5zDE7sejwzy/+kyYSfouUgWyxV/XdL9zNVbl2h/fAnFBh9YG/tyAR9dcoVV5l3fGZToBVtzRni9xrZhDqPUd5xdBJfxqk+fEissk28keT3hhhNwDABQtA5L1o3fYIzQi8KH9aVOuCacuN5Z7v21Rg0ZIqO8Klotr1EUc4h+SE5r9LI7n7f4D2bPXr14m1Situqc7xuOI++w==', 'dMxs+Kc6dIFGQpVN', 'G2hwaHzJRN4ND5zSt2axKQ==', '1cbd8baa25c618748d6fac4e07071b6bc24387993f6dc83aab004db273d754e9', '2026-05-14 13:28:58'),
(2, 2, 'Screenshot (63).png', 'ed32a435389e368519a38bbcc03d855f.enc', 23177, 'Kvi8zCTICMQChzmjtOpC0bL3q5E0W431rMPEhE8juA5C/P5/UzhuvmCVRQQqajQcBlerns6+SY+ywZ8Oj77h+By3eSwLB+CCXDm15+/XG/4nz4ZygGizpYNWzEmlSQxLfWsea8o9pHjMF2l5N7sAyoETZ/TKw2pNTSegxJpgFIiwMuZMUH26ER4yXJX+g5OhRuFll/4KipG8u+CkumT5u/dWKRc1nlEdglMOUx8kzGC2+mSfacZQwWMqHl/YvOII9SzvetlpziL29SOh2+i5Y+GfCNGSaDUVo+CBbBnLxZ/qTuhBgXfZSnJ+pfLvucWh4lJMQ3eaX48gCJ8Z3klzBQ==', 'A0Xwr61gucG/hy04', 'PvKS5Mjyusd6DT9ywgr0TA==', '747991dafe0e38b93dace9e7d378a872e6f9f8601470586d38058b5318b9a7e7', '2026-05-23 14:17:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `file_shares`
--

CREATE TABLE `file_shares` (
  `id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `shared_by` int(11) NOT NULL,
  `shared_to` int(11) NOT NULL,
  `encrypted_key` text NOT NULL,
  `shared_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `file_shares`
--

INSERT INTO `file_shares` (`id`, `file_id`, `shared_by`, `shared_to`, `encrypted_key`, `shared_at`) VALUES
(1, 1, 1, 2, 'jpWM/XYh4Qy9gRvibTOR1VGVVj7z5S1A6QIInMtE4u+ok79WkyPWhUW+/yKZCHVJjXqPw487kDeeN3A6jTnbdZ7y6Y8clKjWkEHiQAA6H1Jbh67j7XsaDwGoMAMQqIWL9f/J3JCpUOhdh0ouwYZZXa3wUzXdbdfgnSDVtCS3fPhyB/KacKkgRD1ndHwsvbicWqnvoyyWnJ+kXIWUyZnM37vPS6b9kje7if5gpBDjAzeezSt98Efwh3EbOZ8aNbGF/uxc7sD0G0HySiFiZ6ES/R7ltgEHQY6F4dyw4GYQHHH/7QMmEpaMxuYhBiOJ1jY+NQHYtwHLSCGuPcQXX+5JWw==', '2026-05-14 14:14:07'),
(2, 2, 2, 1, 'hntbWcDkJng5gEahkvABwskXpgeKp4ZLnMMr27+gDCmQRc0WZ/QKnkLg2UMbJKGKC+kP4qtZK5jcL3uWYvi8woYpxVP3kLlX7exxQ2aU2JhO2lrdTpUk4/XuNxttar7AVq4k3jLbGTPWfSgpB/of5kQCIjdERUxXVkhd0j7SuY4mf6pO5+QnLvzLJP2ETIMIIhpHBOReA0vvGuFOTgH6FFO2uYYLXgFhvHQgIWjpJf+3TGJxKNNfBECuAgYG39dsu+qrDtDX41PggdN4UBRxp4qUrmPmAdLEFp+dAqamDgYs/XoykFqp97M+cMNPuE9H64TqSzKaBvdOeKvZqMCC1A==', '2026-05-23 14:17:27');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `password_hash` varchar(255) NOT NULL,
  `public_key` text NOT NULL,
  `private_key_enc` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `is_admin`, `password_hash`, `public_key`, `private_key_enc`, `created_at`) VALUES
(1, 'tamkha', 'tamamsantuy15@gmail.com', 1, '$2y$12$uwZB.LcseLH7ZEwA0Dr5W.ikB2RlaFggvFENSTe.DuCgkBolcnA86', '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAs50TdII5f8hep+rF9sIm\nl5TEFjZ5dWywA8SNpSlJFtrljYU/UETF1dF07w/7Q7ShcGleD1Dna9furB3pbsg0\nCacaq5uyrsOZ3ioimyw/q8N44hxtbNTjo6I4sMJlTPaWrahRXJSIMDgDtZpZnjKl\n6wEfmB4TBRVwST2B9X+rfvzImDB7//IaGGYWIVCl2N1D3zFfIe3hVFRM+8I0TSbU\n+FM8LGQxJSfSyNw+tEpeOI4h7W2g6BKgNo0SenZGFButGiGZxEBeha3Y18I6yM/D\nESEjPyFN4XB8UApKB4hbmcAdjqeNWdQp9FyNbIKPtTKrv5iPrSuatBAVuFnEAjYq\nCQIDAQAB\n-----END PUBLIC KEY-----\n', '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', '2026-05-14 13:28:08'),
(2, 'user2', 'user2@mail.com', 0, '$2y$12$TZIY8rR3Hd3wJfZXETMD4.bHwADEG1Wu5anX9wkDDwR8GySszQgN6', '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAm/BFPhUsA/fk8La6PcUu\nmCB5eds6olLY0APlL4I2fxHjqa47rnDudLQgvh4MiZ51toDUH/layYmgvopmIiUi\nuTY0husYFt5hEOkCA69vmE7LEZ76Xhp9YslPhyp6UqnSUIFba/yH4wOXBKj7Ym9O\nrLJRwt/1wequvVQP0+z+3wlJl8gRjVeaLneOkOccFddmS06UJRNzEEdenSovmtWP\n/p7vyr/4q3ENSPRzIE1CI+Wm/Xi/2GTaQAXUjZhlXgpM6Xwi0/Jqs4bgHaQajncS\nhj15Byuhm+oSik7zXJG4APo//aRmlSedw9JkxqKhR6peyisVCpCctAACmkqUSTWt\nmQIDAQAB\n-----END PUBLIC KEY-----\n', 'Dn1dhYsoq7iHceIbx2tvICSD7cNb38fBlUbSNaKj9q0VKql+vTDJXBmaq5aStHQNNSY/0gV850h6xozH7sslGkZ8UbFoZgZ8PnoSKuxHrG9mcCvp8B1jaKwFCWehLR+DGobqJgxqF6MHexIcS886MB3Tvjj3NtVzsHQ6B5DR7/WZRVZ1HQEmuZjLGl851uLgFnjsw9ukwm9DqEvvb/pmChuZ0LlkFh+iNUNHYhKaOMTxCDIKr//ptqcnBraJl3tGH773kaDIf1nRXLWBy8G9XHAOSPNMykbLw6+05f6HXlv4bl1xNXYtbbDKm97SocIknwDU5RYQRl+3QZb2pOcFA7EfajH0p7UW80MM6mfNH45JmnOyJpEHXM7VstsP0cmPhE0pPr94SuT+WNqeF4w+4+dWePiLe0w97cizTIdTm3W+mIz5Pb+Ej00Ddz8SLP/WZJR/JEyfXsTCUFkoCsOYDKsdoh7KKDX4l82vWcHq7LMM8yac3YWiy25NhqWHpFds9iQpQURUH8V5JPG5eLYsKtVjMNItGXgkE0l8e8mwfJdB1friSXLMSJeg3j9UDr/MQOct2vsK8O24NRTXPQJzhnvVvPqkOH4c0E7/qBbm6ar+ADjbiHR/gddz8EIeEXNGhW4U1qrB2a6R+n5teCqlhTzquAKfjnfKqI/NuyPdU6Bytfk9UU0UZ4TROClWG1jazwlBDYI+IVwab+sZQeLhnRwYu4Yo+wo0Wi6PXP6Mjw5MDCGcvtAJSbpDCIEzZMGlNNKIsaNervi5K5CepuRXv+7xpS1UFNkyNsztZlHoA5Z9KgMg7E/2xfLXLFWHUq/9w5JA6QTmlpfq1SArUckSuvBgcnDn3jegBtySEUISKgYWkQC2oZS0Y54jJ0DK/3GomV0CguxltG21iSFTFpPK9bEkJvlaEZCeFziu70JOLc1VSz+U03UDReZkHkY9BzQctKWCstf9a67jUTba8cUqk4rcF0xwqNtGCJ5OUuLW7Ymp2jZcYuQ9RBDQPblh+MwTVKcAwZR7LKtnbC4Du5gv+UywL1F0/N7sb2yEL+CKenwbnZqyWBaZB2KR5xXUuA0mAe4xCmxsG8MKkhhBnHTfy6XDPhmOWNrh4kNelSWCP7ZFlTJDhem33pgW+qZ1l+QfV4lC82aP0a0LDLyrl3DFsNRxu++7MCFn6Cx97vrsmck3NX+vvP2/ECDJWd6HlHCC724h/6M03emp6yPIKmZTU1qKq+sGesCXQ786Pk6djlDnXd9zczd10fNyiOsr/jGAj2w5QaRMQtNp8hgAn7CSnemebk4KvkRK/a7V36b/1ItB6Hv67rKL/PNsNdDSNST6Hmqillmd3tTk8/+l6Fwg1XaZz6xtZGgecibpw7anord49LuBT2YmfxI1qaRmIIH6KdNM2IyLg/Rx/anYhqTYC4RkyZZDx5s8hW8Jy8v9mS4ajTlVrQ+WsyCCMNm946CRVwv6orBmFItYBUgOLfVMxHtPwuzYf809aJX/VJm8bNy1FLwoI95r7vZ2E6bFHO53aRD41WtrRBWgKYEZ36i3o+dorpliM2HYxutxuwRHz1RfgqVuZbBR+vaTP674DdF60eI/iBjPsUVgWef1MS95FGIXM/RXLXBS9f73e6TrOz7nS+SIu8C9fHYCnK2Kuc7EYFnf9FpRLvtvaoqpir73lRNaVPlwLrqujMwtEgguT1oT9/MUW5boYnyod5/Ai2EXqu+OzZVUUPjLWhagagxVtkni5uVrWVTO2OJmhK8hk69mw+J2xfboAcRfNtfq9zT6/4MwF4k9BAXF3x5+DnW1f0u07XQFCzCJ2HtltU7ZhaNFfZuDSDaxDiBKwu32mv48mN94QHfG53gIaCAl5phFKedFX9pPnX7L2cK1ZrUyIMsjpBQqcOvwFB1Mfmhb2OqP28/7mpHP0Vyeba8cqXvIVAPydwZXj9vIv1KI1vNwC2lk8/ZNl6Luu5wMSRJhKop7Pn43EI3miPOYwXaUTdVo54yhwaKKhZ7sTq65GGuooNPXl3ENqMpnWg8W027pfVVbfB26x04eacvYlD+so2wlqEJjLeqfBBeeMAGYp/DfcSOfI0ZtaPUZ7Jidjl0Zw6Jf+rnBmW8ucaKlx6nYZrXQFQktQ3dHjoNB3FwsJC+lJqE+j4pJthQQ7+Xit9Qa0b6dUa1XVIuHsFxwOmxvG+yupTyD+UnigNfOQg4XN5b3n4kem5U+WC+7IkazzM7mNq29oAwPXKu5pLhi4hiN0cNF8RcKmq5y2D+wkyRicCy/2yv7BF+k8iV2UZOUhDlk161CrGRBQ/SnE0tceZHjHrEMIg==', '2026-05-14 14:11:22');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indeks untuk tabel `file_shares`
--
ALTER TABLE `file_shares`
  ADD PRIMARY KEY (`id`),
  ADD KEY `file_id` (`file_id`),
  ADD KEY `shared_by` (`shared_by`),
  ADD KEY `shared_to` (`shared_to`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `file_shares`
--
ALTER TABLE `file_shares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `files_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `file_shares`
--
ALTER TABLE `file_shares`
  ADD CONSTRAINT `file_shares_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_shares_ibfk_2` FOREIGN KEY (`shared_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `file_shares_ibfk_3` FOREIGN KEY (`shared_to`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
