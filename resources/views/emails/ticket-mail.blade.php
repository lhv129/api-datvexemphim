<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Vé xem phim của bạn</title>
</head>
<body>
    <h2>Xin chào,</h2>
    <p>Cảm ơn bạn đã đặt vé tại FilmGo. Dưới đây là thông tin vé của bạn:</p>

    <h3>Thông tin vé:</h3>
    <ul>
        <li><strong>Mã vé:</strong> {{ $emailData['ticket_code'] }}</li>
        <li><strong>Tên phim:</strong> {{ $emailData['movie_name'] }}</li>
        <li><strong>Rạp:</strong> {{ $emailData['cinema_name'] }}</li>
        <li><strong>Phòng chiếu:</strong> {{ $emailData['screen_name'] }}</li>
        <li><strong>Suất chiếu:</strong> {{ $emailData['show_time'] }}</li>
        <li><strong>Ghế:</strong> {{ $emailData['seats'] }}</li>
        <li><strong>Giá:</strong> {{ $emailData['price'] }}</li>
        <li><strong>Sản phẩm:</strong> {{ $emailData['products'] }}</li>
        <li><strong>Khuyến mãi:</strong> {{ $emailData['promotion'] }}</li>
        <li><strong>Tổng cộng:</strong> {{ $emailData['total_amount'] }}</li>
    </ul>

    <p>Vui lòng đưa mã vé này đến quầy vé để nhận vé.</p>

    <p>Chúc bạn có một trải nghiệm xem phim tuyệt vời! 🎬</p>

    <p><strong>FilmGo Cinemas Việt Nam</strong></p>
    <p>Email hỗ trợ: support@filmgo.vn</p>
    <p>Hotline: 1900 6017</p>
</body>
</html>
