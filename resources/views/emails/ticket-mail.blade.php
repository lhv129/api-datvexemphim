<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Vé xem phim của bạn</title>
</head>
<body>
    <h2>Xin chào {{ $emailData['name'] }},</h2>
    <p>Cảm ơn bạn đã đặt vé tại FilmGo. Dưới đây là thông tin vé của bạn:</p>

    <h3>Thông tin vé:</h3>
    <ul>
        <li><strong>Phim:</strong> {{ $emailData['movie_name'] }}</li>
        <li><strong>Rạp:</strong> {{ $emailData['cinema_name'] }}</li>
        <li><strong>Thời gian:</strong> {{ $emailData['show_time'] }}</li>
        <li><strong>Mã vé:</strong> {{ $emailData['ticket_code'] }}</li>
    </ul>

    <p>Chúc bạn có một trải nghiệm xem phim tuyệt vời! 🎬</p>
</body>
</html>
