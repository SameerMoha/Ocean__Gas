<?php
// -----------------------------
// Handle form submission
// -----------------------------
$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) connect
    $conn = new mysqli('localhost', 'root', '', 'oceangas');
    if ($conn->connect_error) {
        $error = 'Database connection failed: ' . $conn->connect_error;
    } else {
        // 2) sanitize inputs
        $name     = $conn->real_escape_string($_POST['name']);
        $email    = $conn->real_escape_string($_POST['email']);
        $location = $conn->real_escape_string($_POST['location']);
        $company  = $conn->real_escape_string($_POST['company']);
        $message  = $conn->real_escape_string($_POST['message']);

        // 3) insert
        $sql = "
          INSERT INTO inquiries 
            (name, email, location, company, message) 
          VALUES 
            ('$name', '$email', '$location', '$company', '$message')
        ";

        if ($conn->query($sql)) {
            $success = true;
        } else {
            $error = 'Insert error: ' . $conn->error;
        }

        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Ocean Gas</title>
    <style>
        /* General Styles */
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            background: #f8f9fa;
            color: #333;
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            background: #0066cc;
            padding: 1rem 3rem;
            position: sticky;
            top: 0;
            
            z-index: 1000;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }

        .navbar img {
            height: 50px;
            margin-right: 20px;
        }

        .nav-links {
            list-style: none;
            display: flex;
            gap: 20px;
            margin-left: auto;
            padding: 0;
        }

        .nav-links li {
            display: inline;
        }

        .nav-links a {
            text-decoration: none;
            color: white;
            font-size: 18px;
            padding: 8px 16px;
            border-radius: 5px;
            transition: 0.3s;
        }

        .nav-links a:hover {
            background: #005bb5;
        }
        /* Responsive */
@media (max-width: 768px) {
    .navbar {
        flex-direction: column;
        align-items: center;
    }
    .nav-links {
        margin-left: 0; /* Center links for smaller screens */
        text-align: center;
        margin-top: 10px;
    }
}

        /* Hero Section */
        .contact-section {
            background: url('https://circlegas.co.uk/wp-content/uploads/2020/01/contact-page-banner.jpg') no-repeat center center/cover;
            color: white;
            text-align: center;
            padding: 5rem 1rem;
            animation: fadeIn 1.5s ease-in-out;
        }

        .contact-section h1 {
            font-size: 3.5rem;
            margin-bottom: 10px;
        }

        .contact-section p {
            font-size: 1.4rem;
            max-width: 700px;
            margin: 0 auto;
        }

        /* Contact Information */
        .contact-info {
            background: white;
            padding: 3rem;
            margin: 2rem auto;
            max-width: 800px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            text-align: left;
            transition: 0.3s;
        }

        .contact-info:hover {
            transform: scale(1.02);
            box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.15);
        }

        .contact-info h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .contact-info p {
            font-size: 1.2rem;
            margin: 0.5rem 0;
        }

        /* Contact Form */
        .contact-form {
    background: #fff;
    padding: 3rem;
    margin: 2rem auto;
    max-width: 700px;
    border-radius: 10px;
    box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.1);
    text-align: center;
    animation: fadeIn 1.2s ease-in-out;
}

.contact-form h2 {
    font-size: 2.5rem;
    color: #f0f3f7;
    margin-bottom: 0.5rem;
}

.contact-form p {
    font-size: 1.2rem;
    color: #555;
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
    text-align: left;
}

label {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    display: block;
    margin-bottom: 0.5rem;
}

label span {
    color: red;
}

input, textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 1rem;
    transition: 0.3s;
    outline: none;
}

input:focus, textarea:focus {
    border-color: #004080;
    box-shadow: 0px 0px 8px rgba(0, 64, 128, 0.2);
}

textarea {
    resize: vertical;
}

button {
    display: inline-block;
    width: 100%;
    padding: 12px;
    background: #004080;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1.2rem;
    font-weight: bold;
    transition: 0.3s;
}

button:hover {
    background: #0066cc;
    transform: scale(1.05);
}

/* Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

        /* Google Maps Section */
        .map-section {
            text-align: center;
            margin: 3rem auto;
            max-width: 900px;
        }

        .map-container {
            width: 100%;
            height: 400px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Quick Links */
        .quick-links {
    background: #000000;
    color: white;
    padding: 3rem 2rem;
    display: flex;
    justify-content: space-between;
    margin: 2rem auto;
    border-radius: 10px;
}

.quick-links h3 {
    margin-bottom: 1rem;
    font-size: 1.5rem;
}

.quick-links ul {
    list-style: none;
    padding: 0;
}

.quick-links ul li {
    margin: 8px 0;
}

.quick-links ul li a {
    text-decoration: none;
    color: white;
    transition: 0.3s;
}

.quick-links ul li a:hover {
    color: #ccc;
}


/* Social Media Icons */
.social-icons {
    margin-top: 1rem;
}

.social-icons a {
    display: inline-block;
    margin-right: 10px;
    color: white;
    font-size: 1.8rem;
    transition: 0.3s;
}

.social-icons a:hover {
    color:white;
    transform: scale(1.1);
}


        /* Footer */
        footer {
            text-align: center;
            padding: 1rem 0;
            background: #004080;
            color: #fff;
            width: 100%;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        #backToTopBtn {
    display: none; /* Hidden by default */
    position: fixed;
    bottom: 15px;
    right: 15px; /* Fixed at the bottom-left */
    z-index: 100;
    font-size: 14px; /* Smaller size */
    border: none;
    outline: none;
    background-color: #007bff;
    color: white;
    cursor: pointer;
    padding: 8px 10px; /* Smaller padding */
    border-radius: 50%;
    width: 35px; /* Smaller width */
    height: 35px; /* Smaller height */
    text-align: center;
    line-height: 18px;
    box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2);
    transition: 0.3s;
}

#backToTopBtn:hover {
    background-color: #0056b3;
    transform: scale(1.1);
}
     

    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <img src="assets\images\Oceangas.png" alt=" Logo">
        <ul class="nav-links">
            <li><a href="index.html">Home</a></li>
            <li><a href="about.html">About</a></li>
            <li><a href="contact.php">Contact</a></li>
        </ul>
    </nav>

    <!-- Hero Section -->
    <section class="contact-section">
        <h1>Contact Us</h1>
        <p>We are here to assist you. Reach out for inquiries, support, or business opportunities.</p>
    </section>

    <!-- Contact Information -->
    <section class="contact-info">
        <h2>Our Contact Information</h2>
        <p><strong>📍 Address:</strong> Wambungu Lane, Nairobi, Kenya.</p>
        <p><strong>📞 Phone:</strong> +254 123 456 789</p>
        <p><strong>✉️ Email:</strong> <a href="mailto:oceangas99@gmail.com">oceangas99@gmail.com</a></p>
    </section>

    <!-- Google Maps -->
    <section class="map-section">
        <h2>Find Us Here</h2>
        <div class="map-container">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3988.8411821893374!2d36.817535474614786!3d-1.2680836987198372!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x182f174ae17d88f1%3A0xd90a4e85f89a5ac3!2sOffice%20Suites!5e0!3m2!1sen!2ske!4v1740237189256!5m2!1sen!2ske">allowfullscreen</iframe>
    </section>
 <!-- Contact Form Section -->
 <section class="contact-form">
    <h2>Send Us a Message</h2>
    <p>We’d love to hear from you! Fill out the form below and we’ll get back to you soon.</p>

    <?php if ($success): ?>
        <p style="color: green; font-weight: bold;">Thank you! Your inquiry has been received.</p>
    <?php elseif ($error): ?>
        <p style="color: red; font-weight: bold;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="contact.php" method="POST">
        <div class="form-group">
            <label for="name">Full Name <span>*</span></label>
            <input type="text" id="name" name="name" placeholder="Enter your full name" required>
        </div>

        <div class="form-group">
            <label for="email">Email Address <span>*</span></label>
            <input type="email" id="email" name="email" placeholder="Enter your email" required>
        </div>

        <div class="form-group">
            <label for="location">Location <span>*</span></label>
            <input type="text" id="location" name="location" placeholder="Your city or country" required>
        </div>

        <div class="form-group">
            <label for="company">Company (Optional)</label>
            <input type="text" id="company" name="company" placeholder="Your company name">
        </div>

        <div class="form-group">
            <label for="message">Message <span>*</span></label>
            <textarea id="message" name="message" rows="5" placeholder="Write your message here..." required></textarea>
        </div>

        <button type="submit">Send Message</button>
    </form>
</section>


    <!-- Quick Links -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <section class="quick-links">
        <div>
            <h3>Quick Links</h3>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="about.html">About Us</a></li>
                <li><a href="contact.html">Contact</a></li>
            </ul>
        </div>
    
        <div>
            <h3>Contact Details</h3>
            <p><strong>📞 Phone:</strong> +254 123 456 789</p>
            <p><strong>✉️ Email:</strong> <a href="mailto:oceangas99@gmail.com" style="color: #fff;">oceangas99@gmail.com</a></p>
    
            <!-- Social Media Links -->
            <div class="social-icons">
                <a href="https://www.instagram.com/" target="_blank"><i class="fab fa-instagram"></i></a>
                <a href="https://twitter.com/" target="_blank"><i class="fab fa-twitter"></i></a>
                <a href="https://facebook.com/" target="_blank"><i class="fab fa-facebook"></i></a>
            </div>
        </div>
    </section>
    <footer>
        <p>&copy; 2025 Ocean Gas Company. All Rights Reserved.</p>
    </footer>
   
<script>
    // Show the button when scrolling down
    window.onscroll = function() {
        var btn = document.getElementById("backToTopBtn");
        if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
            btn.style.display = "block";
        } else {
            btn.style.display = "none";
        }
    };

    // Scroll back to top when button is clicked
    function scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
</script>
<button onclick="scrollToTop()" id="backToTopBtn" title="Go to top">↑</button> 
</body>
</html>