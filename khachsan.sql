-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th3 20, 2026 lúc 04:24 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `khachsan`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chi_tiet_dich_vu`
--

CREATE TABLE `chi_tiet_dich_vu` (
  `id` int(11) NOT NULL,
  `dat_phong_id` int(11) NOT NULL,
  `ma_dich_vu` varchar(20) NOT NULL,
  `so_luong` int(11) NOT NULL DEFAULT 1,
  `thanh_tien` decimal(10,2) NOT NULL,
  `thoi_gian_su_dung` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `chi_tiet_dich_vu`
--

INSERT INTO `chi_tiet_dich_vu` (`id`, `dat_phong_id`, `ma_dich_vu`, `so_luong`, `thanh_tien`, `thoi_gian_su_dung`) VALUES
(2, 13, 'DV1444', 1, 200000.00, '2026-03-19 15:32:47');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dat_phong`
--

CREATE TABLE `dat_phong` (
  `id` int(11) NOT NULL,
  `booking_id` varchar(20) NOT NULL,
  `khach_hang_cccd` varchar(20) NOT NULL,
  `ma_nv` varchar(20) DEFAULT NULL,
  `ma_phong` varchar(20) DEFAULT NULL,
  `ma_loai` varchar(20) NOT NULL,
  `ngay_den` date NOT NULL,
  `ngay_di` date NOT NULL,
  `so_khach` int(11) NOT NULL,
  `tong_tien_du_kien` decimal(10,2) DEFAULT NULL,
  `trang_thai` enum('Đã đặt trước','Tạm giữ thanh toán','Đã đặt cọc','Đã thanh toán đầy đủ','Đã check-in','Đã check-out','Đã hủy') DEFAULT 'Tạm giữ thanh toán',
  `ngay_dat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `dat_phong`
--

INSERT INTO `dat_phong` (`id`, `booking_id`, `khach_hang_cccd`, `ma_nv`, `ma_phong`, `ma_loai`, `ngay_den`, `ngay_di`, `so_khach`, `tong_tien_du_kien`, `trang_thai`, `ngay_dat`) VALUES
(8, 'BK20260319-4415', '1234567890', NULL, 'P103', 'LP_STD', '2026-03-19', '2026-03-20', 2, 1000000.00, 'Đã hủy', '2026-03-19 03:07:46'),
(11, 'Q-20260319-602', '1357', 'admin01', 'P103', 'LP_STD', '2026-03-19', '2026-03-20', 1, 1000000.00, 'Đã hủy', '2026-03-19 15:13:05'),
(12, 'Q-20260319-772', '1357', 'admin01', 'P103', 'LP_STD', '2026-03-19', '2026-03-20', 1, 1000000.00, 'Đã hủy', '2026-03-19 15:15:27'),
(13, 'BK-20260319-6025', '1357', 'admin01', 'P103', 'LP_STD', '2026-03-19', '2026-03-20', 1, 1000000.00, 'Đã hủy', '2026-03-19 15:19:55');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dich_vu`
--

CREATE TABLE `dich_vu` (
  `ma_dich_vu` varchar(20) NOT NULL,
  `ten_dich_vu` varchar(100) NOT NULL,
  `don_gia` decimal(10,2) NOT NULL,
  `mo_ta` text NOT NULL,
  `url_anh` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `dich_vu`
--

INSERT INTO `dich_vu` (`ma_dich_vu`, `ten_dich_vu`, `don_gia`, `mo_ta`, `url_anh`) VALUES
('DV1444', 'Yoga', 200000.00, 'Dịch vụ Yoga mang đến không gian yên bình lý tưởng để bạn tái tạo năng lượng và tìm lại sự cân bằng giữa thân - tâm - trí. Với các bài tập đa dạng cùng sự hướng dẫn tận tâm, đây là hành trình giúp bạn cải thiện sức khỏe, giải tỏa căng thẳng và tận hưởng những giây phút thư thái tuyệt đối trong một môi trường thư giãn hoàn hảo.', 'https://anviethouse.vn/wp-content/uploads/2023/03/Thiet-ke-phong-tap-yoga-5-1-1.jpg'),
('DV5190', 'Phòng Hội nghị cao cấp', 400000.00, 'Phòng hội nghị cao cấp sở hữu không gian sang trọng, hiện đại với hệ thống âm thanh, ánh sáng và màn hình LED thế hệ mới, đáp ứng hoàn hảo mọi nhu cầu tổ chức sự kiện chuyên nghiệp. Với thiết kế linh hoạt cùng đội ngũ hỗ trợ tận tâm, đây là lựa chọn lý tưởng cho các buổi ký kết quan trọng, hội thảo chiến lược hay những sự kiện doanh nghiệp đẳng cấp, đảm bảo mang lại sự thành công và ấn tượng trọn vẹn cho quý đối tác.', 'https://crystalpalacevn.com/wp-content/uploads/2016/12/to-chuc-hoi-nghi-hoi-thao-tai-khach-san-melia.jpg'),
('DV6300', 'Quầy bar', 150000.00, 'Quầy bar mang đến một không gian thư giãn lý tưởng với thực đơn đồ uống phong phú, từ những ly cocktail sáng tạo đến các dòng rượu vang thượng hạng. Trong không gian sang trọng kết hợp cùng âm nhạc tinh tế và kỹ nghệ pha chế chuyên nghiệp, đây là nơi hoàn hảo để bạn tận hưởng những khoảnh khắc \'chill\' nhẹ nhàng hay gặp gỡ bạn bè sau một ngày dài.', 'https://d2e5ushqwiltxm.cloudfront.net/wp-content/uploads/sites/83/2016/12/06092551/Mint-Bar-11-700x700.jpg'),
('DV7637', 'Spa', 300000.00, 'Dịch vụ Spa là không gian thư giãn tuyệt vời, nơi bạn có thể tạm rời xa những ồn ào để nuông chiều cơ thể và tâm trí. Thông qua các liệu pháp massage tinh tế hòa quyện cùng hương thơm thảo mộc và quy trình chăm sóc da chuyên sâu, chúng tôi giúp bạn xua tan mọi mệt mỏi, tái tạo năng lượng và lấy lại vẻ rạng rỡ vốn có trong một không gian yên bình, sang trọng.', 'https://hssc.vn/wp-content/uploads/2023/04/2-1.png'),
('DV8405', 'Giặt ủi ', 250000.00, 'Dịch vụ Giặt ủi chuyên nghiệp giúp bạn luôn xuất hiện với diện mạo chỉn chu và tự tin nhất trong suốt kỳ lưu trú. Với quy trình xử lý hiện đại, sử dụng các sản phẩm làm sạch dịu nhẹ và công nghệ chăm sóc vải tiên tiến, chúng tôi đảm bảo trang phục của bạn luôn sạch thơm, phẳng phiu và giữ trọn độ bền đẹp như mới một cách nhanh chóng, tiện lợi.', 'https://kienthucnganhdulich.edu.vn/wp-content/uploads/2024/07/Cong-viec-cu-the-cua-mot-nhan-vien-giat-la-tai-khach-san-.jpg'),
('DV9556', 'Sân Golf', 600000.00, 'Hệ thống Sân Golf tiêu chuẩn quốc tế mang đến trải nghiệm thể thao đẳng cấp giữa không gian xanh mướt và tầm nhìn toàn cảnh tuyệt đẹp. Với thiết kế địa hình đầy thách thức nhưng không kém phần thi vị, cùng dịch vụ caddy chuyên nghiệp và trang thiết bị hiện đại, đây là điểm đến lý tưởng để các gôn thủ rèn luyện kỹ thuật, tận hưởng những cú swing chuẩn xác và thư giãn tinh thần trong bầu không khí trong lành.', 'https://elinkgolf.vn/wp-content/uploads/2020/11/san-golf-asean-resort.jpg');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hoa_don`
--

CREATE TABLE `hoa_don` (
  `ma_hoa_don` varchar(20) NOT NULL,
  `dat_phong_id` int(11) NOT NULL,
  `ma_nv` varchar(20) NOT NULL,
  `ma_khuyen_mai` varchar(20) DEFAULT NULL,
  `tien_phong` decimal(10,2) NOT NULL,
  `tien_dich_vu` decimal(10,2) DEFAULT 0.00,
  `thue_vat` decimal(10,2) DEFAULT 0.00,
  `tien_coc` decimal(10,2) DEFAULT 0.00,
  `tien_phu_phi` decimal(10,2) DEFAULT 0.00,
  `tong_tien_thanh_toan` decimal(10,2) NOT NULL,
  `phuong_thuc_thanh_toan` enum('Tiền mặt','Chuyển khoản') DEFAULT 'Tiền mặt',
  `trang_thai_thanh_toan` enum('Chưa thanh toán','Đã thanh toán') DEFAULT 'Chưa thanh toán',
  `ngay_thanh_toan` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `hoa_don`
--

INSERT INTO `hoa_don` (`ma_hoa_don`, `dat_phong_id`, `ma_nv`, `ma_khuyen_mai`, `tien_phong`, `tien_dich_vu`, `thue_vat`, `tien_coc`, `tien_phu_phi`, `tong_tien_thanh_toan`, `phuong_thuc_thanh_toan`, `trang_thai_thanh_toan`, `ngay_thanh_toan`) VALUES
('HD1773889669', 8, 'admin01', NULL, 1000000.00, 0.00, 0.00, 0.00, 0.00, 1000000.00, 'Tiền mặt', 'Đã thanh toán', '2026-03-20 02:38:07'),
('HD20260319358', 13, 'admin01', NULL, 1000000.00, 200000.00, 0.00, 0.00, 0.00, 1200000.00, 'Tiền mặt', 'Đã thanh toán', '2026-03-19 15:37:27'),
('HD20260319866', 12, 'admin01', NULL, 1000000.00, 0.00, 0.00, 0.00, 0.00, 1000000.00, 'Tiền mặt', 'Chưa thanh toán', '2026-03-19 15:15:27'),
('HD20260319926', 11, 'admin01', NULL, 1000000.00, 0.00, 0.00, 0.00, 0.00, 1000000.00, 'Tiền mặt', 'Chưa thanh toán', '2026-03-19 15:13:05');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khach_hang`
--

CREATE TABLE `khach_hang` (
  `cccd_ho_chieu` varchar(20) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `so_dien_thoai` varchar(15) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `mat_khau` varchar(255) NOT NULL,
  `cong_no` decimal(10,2) DEFAULT 0.00,
  `so_lan_huy` int(11) DEFAULT 0,
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp(),
  `ma_khach_hang` varchar(0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `khach_hang`
--

INSERT INTO `khach_hang` (`cccd_ho_chieu`, `ho_ten`, `so_dien_thoai`, `email`, `mat_khau`, `cong_no`, `so_lan_huy`, `ngay_tao`, `ma_khach_hang`) VALUES
('00000000', 'Quỳnh', '369963369', 'quynhday@gmail.com', '$2y$10$/yV8dKVQqN8sV6v2gQqzbO81AdEkTEhXBGxxPSv4YoI9zW2jNC3h6', 0.00, 0, '2026-03-17 09:14:07', ''),
('123456', 'huy', '12345678', '123456@gmail.com', '$2y$10$WoARbdB4TXGSO9gXyCxyqeeAljY8acbMmrJ/8pFQSscaBUv9Ec8wS', 0.00, 0, '2026-03-14 16:00:56', ''),
('1234567890', 'Lê Thị Tình', '123456', 'Tinh@123456', '$2y$10$jROsiGHw5TvYPeyQfTgGI.gD9TwyCIz7LoyId3lmyVRnesdfVgoG6', 0.00, 0, '2026-03-18 10:40:49', ''),
('1357', 'Nguyễn Thị Hồng', '987654321', NULL, '123456', 0.00, 0, '2026-03-19 15:09:12', ''),
('7777777', 'andry', '7777777', '7777777@gmail.com', '$2y$10$fVUNz89F1hrS1q3XATYhlukH8Gjmq/d0d5XgkuJKEkP9SlakoC7XC', 0.00, 0, '2026-03-15 03:07:05', ''),
('888888', 'Võ Kim yến', '888888', '888888@gmail.com', '$2y$10$gHJEu3DPmnqoXNMZyUtnKumt6HFbMxgOcS2FHWQwpoZchg3wWQ3Iy', 0.00, 0, '2026-03-15 16:04:35', '');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khuyen_mai`
--

CREATE TABLE `khuyen_mai` (
  `ma_khuyen_mai` varchar(20) NOT NULL,
  `phan_tram_giam` int(11) NOT NULL,
  `ngay_bat_dau` date DEFAULT NULL,
  `ngay_ket_thuc` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lich_su_huy`
--

CREATE TABLE `lich_su_huy` (
  `id` int(11) NOT NULL,
  `dat_phong_id` int(11) NOT NULL,
  `thoi_diem_huy` timestamp NOT NULL DEFAULT current_timestamp(),
  `phi_huy` decimal(10,2) DEFAULT 0.00,
  `tien_hoan_tra` decimal(10,2) DEFAULT 0.00,
  `ly_do` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `loai_phong`
--

CREATE TABLE `loai_phong` (
  `ma_loai` varchar(20) NOT NULL,
  `ten_loai` varchar(50) NOT NULL,
  `gia_dem` decimal(10,2) NOT NULL,
  `dien_tich` int(11) DEFAULT NULL,
  `suc_chua` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `loai_phong`
--

INSERT INTO `loai_phong` (`ma_loai`, `ten_loai`, `gia_dem`, `dien_tich`, `suc_chua`) VALUES
('LP_DLX', 'Deluxe City View', 1500000.00, 35, 3),
('LP_STD', 'Standard Room', 1000000.00, 25, 2),
('LP_SUT', 'Executive Suite', 3000000.00, 50, 4);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhan_vien`
--

CREATE TABLE `nhan_vien` (
  `ma_nv` varchar(20) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `vai_tro` enum('Lễ tân','Quản lý','Buồng phòng') DEFAULT 'Lễ tân',
  `mat_khau` varchar(255) NOT NULL,
  `trang_thai` enum('Đang làm việc','Đã nghỉ') DEFAULT 'Đang làm việc',
  `cccd_ho_chieu` text NOT NULL,
  `email` text NOT NULL,
  `so_dien_thoai` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `nhan_vien`
--

INSERT INTO `nhan_vien` (`ma_nv`, `ho_ten`, `vai_tro`, `mat_khau`, `trang_thai`, `cccd_ho_chieu`, `email`, `so_dien_thoai`) VALUES
('admin01', 'Quản Trị Viên', 'Quản lý', '123456', 'Đang làm việc', '222222', '222222@gmail.com', '222222');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phong`
--

CREATE TABLE `phong` (
  `ma_phong` varchar(20) NOT NULL,
  `ten_phong` varchar(100) DEFAULT NULL,
  `loai_phong` varchar(50) DEFAULT NULL,
  `gia_phong` decimal(10,2) DEFAULT NULL,
  `ma_loai` varchar(20) NOT NULL,
  `trang_thai` enum('Trống','Đang lưu trú','Đang dọn dẹp','Bảo trì') DEFAULT 'Trống',
  `url_phong` text NOT NULL,
  `chi_tiet_phong` text NOT NULL,
  `so_phong_trong` int(11) DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `phong`
--

INSERT INTO `phong` (`ma_phong`, `ten_phong`, `loai_phong`, `gia_phong`, `ma_loai`, `trang_thai`, `url_phong`, `chi_tiet_phong`, `so_phong_trong`) VALUES
('P101', 'Deluxe', NULL, 2000000.00, 'LP_STD', 'Trống', 'https://studiochupanhdep.com/Upload/Newsimages/phong-khach-san-tt-studio.jpg', 'Phòng Deluxe mang đến không gian nghỉ dưỡng sang trọng với thiết kế tinh tế, nội thất hiện đại và diện tích rộng rãi. Sở hữu tầm nhìn ấn tượng cùng các tiện nghi cao cấp, đây là lựa chọn hoàn hảo cho những ai tìm kiếm sự thoải mái, riêng tư và trải nghiệm đẳng cấp trong suốt kỳ lưu trú.', 7),
('P102', 'Suite', NULL, 1500000.00, 'LP_STD', 'Trống', 'https://amanaki.vn/_next/image?url=https%3A%2F%2Fcms.amanaki.vn%2Fuploads%2Fphong_suite_2_854c863d8b.jpg&w=3840&q=75', 'Phòng Suite là hạng phòng cao cấp và đắt giá nhất trong khách sạn, mang đến trải nghiệm lưu trú xa hoa với không gian vô cùng rộng rãi, thường bao gồm phòng khách và phòng ngủ hoàn toàn riêng biệt. Nằm ở những vị trí đắc địa nhất với tầm nhìn toàn cảnh tuyệt đẹp, phòng luôn được trang bị nội thất xa xỉ, phòng tắm lớn với bồn tắm thư giãn và các tiện nghi giải trí hiện đại nhất. Không chỉ dừng lại ở không gian vật chất, điểm nhấn thực sự của hạng phòng này còn nằm ở các đặc quyền dịch vụ VIP đi kèm như quản gia cá nhân (butler), quyền truy cập Club Lounge thương gia, thủ tục ưu tiên và các tiện ích chăm sóc cá nhân hóa đẳng cấp.', 6),
('P103', 'Standard', NULL, 1000000.00, 'LP_STD', 'Trống', 'https://www.vietnambooking.com/wp-content/uploads/2018/06/co-nhung-loai-phong-khach-san-nao-02-06-18-1.jpg', 'Phòng Standard  là hạng phòng cơ bản nhất trong bất kỳ khách sạn nào, được thiết kế tối giản để đáp ứng nhu cầu lưu trú thiết yếu với mức giá tiết kiệm nhất. Loại phòng này thường có diện tích nhỏ gọn, nằm ở những tầng thấp hoặc khu vực không có tầm nhìn đặc biệt (view nội khu hoặc hướng tường), nhưng vẫn đảm bảo đầy đủ các trang thiết bị cơ bản như giường ngủ, tủ quần áo, tivi, điều hòa và nhà vệ sinh riêng sạch sẽ. Đây là lựa chọn tối ưu cho những khách hàng đi công tác ngắn ngày hoặc khách du lịch muốn dành phần lớn thời gian ở bên ngoài và chỉ cần một nơi nghỉ ngơi an toàn, tiện lợi với chi phí thấp.', 4);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `chi_tiet_dich_vu`
--
ALTER TABLE `chi_tiet_dich_vu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dat_phong_id` (`dat_phong_id`),
  ADD KEY `ma_dich_vu` (`ma_dich_vu`);

--
-- Chỉ mục cho bảng `dat_phong`
--
ALTER TABLE `dat_phong`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`),
  ADD KEY `khach_hang_cccd` (`khach_hang_cccd`),
  ADD KEY `ma_nv` (`ma_nv`),
  ADD KEY `ma_phong` (`ma_phong`),
  ADD KEY `ma_loai` (`ma_loai`);

--
-- Chỉ mục cho bảng `dich_vu`
--
ALTER TABLE `dich_vu`
  ADD PRIMARY KEY (`ma_dich_vu`);

--
-- Chỉ mục cho bảng `hoa_don`
--
ALTER TABLE `hoa_don`
  ADD PRIMARY KEY (`ma_hoa_don`),
  ADD KEY `dat_phong_id` (`dat_phong_id`),
  ADD KEY `ma_nv` (`ma_nv`),
  ADD KEY `ma_khuyen_mai` (`ma_khuyen_mai`);

--
-- Chỉ mục cho bảng `khach_hang`
--
ALTER TABLE `khach_hang`
  ADD PRIMARY KEY (`cccd_ho_chieu`),
  ADD UNIQUE KEY `so_dien_thoai` (`so_dien_thoai`);

--
-- Chỉ mục cho bảng `khuyen_mai`
--
ALTER TABLE `khuyen_mai`
  ADD PRIMARY KEY (`ma_khuyen_mai`);

--
-- Chỉ mục cho bảng `lich_su_huy`
--
ALTER TABLE `lich_su_huy`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dat_phong_id` (`dat_phong_id`);

--
-- Chỉ mục cho bảng `loai_phong`
--
ALTER TABLE `loai_phong`
  ADD PRIMARY KEY (`ma_loai`);

--
-- Chỉ mục cho bảng `nhan_vien`
--
ALTER TABLE `nhan_vien`
  ADD PRIMARY KEY (`ma_nv`);

--
-- Chỉ mục cho bảng `phong`
--
ALTER TABLE `phong`
  ADD PRIMARY KEY (`ma_phong`),
  ADD KEY `ma_loai` (`ma_loai`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `chi_tiet_dich_vu`
--
ALTER TABLE `chi_tiet_dich_vu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `dat_phong`
--
ALTER TABLE `dat_phong`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT cho bảng `lich_su_huy`
--
ALTER TABLE `lich_su_huy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `chi_tiet_dich_vu`
--
ALTER TABLE `chi_tiet_dich_vu`
  ADD CONSTRAINT `chi_tiet_dich_vu_ibfk_1` FOREIGN KEY (`dat_phong_id`) REFERENCES `dat_phong` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `chi_tiet_dich_vu_ibfk_2` FOREIGN KEY (`ma_dich_vu`) REFERENCES `dich_vu` (`ma_dich_vu`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `dat_phong`
--
ALTER TABLE `dat_phong`
  ADD CONSTRAINT `dat_phong_ibfk_1` FOREIGN KEY (`khach_hang_cccd`) REFERENCES `khach_hang` (`cccd_ho_chieu`) ON UPDATE CASCADE,
  ADD CONSTRAINT `dat_phong_ibfk_2` FOREIGN KEY (`ma_nv`) REFERENCES `nhan_vien` (`ma_nv`) ON UPDATE CASCADE,
  ADD CONSTRAINT `dat_phong_ibfk_3` FOREIGN KEY (`ma_phong`) REFERENCES `phong` (`ma_phong`) ON UPDATE CASCADE,
  ADD CONSTRAINT `dat_phong_ibfk_4` FOREIGN KEY (`ma_loai`) REFERENCES `loai_phong` (`ma_loai`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `hoa_don`
--
ALTER TABLE `hoa_don`
  ADD CONSTRAINT `hoa_don_ibfk_1` FOREIGN KEY (`dat_phong_id`) REFERENCES `dat_phong` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `hoa_don_ibfk_2` FOREIGN KEY (`ma_nv`) REFERENCES `nhan_vien` (`ma_nv`) ON UPDATE CASCADE,
  ADD CONSTRAINT `hoa_don_ibfk_3` FOREIGN KEY (`ma_khuyen_mai`) REFERENCES `khuyen_mai` (`ma_khuyen_mai`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `lich_su_huy`
--
ALTER TABLE `lich_su_huy`
  ADD CONSTRAINT `lich_su_huy_ibfk_1` FOREIGN KEY (`dat_phong_id`) REFERENCES `dat_phong` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `phong`
--
ALTER TABLE `phong`
  ADD CONSTRAINT `phong_ibfk_1` FOREIGN KEY (`ma_loai`) REFERENCES `loai_phong` (`ma_loai`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
