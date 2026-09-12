<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Rate Your Experience</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #fff; }
        .rating-card { border: none; padding: 40px; }
        .star-rating { color: #ddd; font-size: 2.5rem; cursor: pointer; transition: 0.2s; }
        .star-rating:hover, .star-rating.active { color: #ffc107; }
        .feedback-box { border-radius: 15px; background-color: #f8f9fa; border: none; padding: 15px; }
        .btn-submit { background-color: #000; border-radius: 12px; padding: 12px 30px; font-weight: 600; }
        .success-icon { font-size: 4rem; color: #198754; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="container py-5 text-center">
    <div class="rating-card mx-auto" style="max-width: 550px;">
        <i class="bi bi-check-circle-fill success-icon"></i>
        <h2 class="fw-bold mb-2">Booking Confirmed!</h2>
        <p class="text-muted mb-5">Thank you for choosing us. We hope you have a wonderful stay. How was your booking experience?</p>

        <form action="profile.php">
            <div class="mb-4">
                <i class="bi bi-star-fill star-rating active"></i>
                <i class="bi bi-star-fill star-rating active"></i>
                <i class="bi bi-star-fill star-rating active"></i>
                <i class="bi bi-star-fill star-rating active"></i>
                <i class="bi bi-star-fill star-rating"></i>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold small text-uppercase">Leave a comment (Optional)</label>
                <textarea class="form-control feedback-box" rows="4" placeholder="Share your feedback with us..."></textarea>
            </div>

            <button type="submit" class="btn btn-submit btn-dark px-5">Submit Review</button>
            <div class="mt-3">
                <a href="profile.php" class="text-muted small text-decoration-none">Skip for now</a>
            </div>
        </form>
    </div>
</div>

<script>
    // สคริปต์ทำดาวให้กดได้จริง (Visual only)
    const stars = document.querySelectorAll('.star-rating');
    stars.forEach((star, index) => {
        star.onclick = () => {
            stars.forEach((s, i) => {
                if(i <= index) s.classList.add('active');
                else s.classList.remove('active');
            });
        };
    });
</script>
</body>
</html>