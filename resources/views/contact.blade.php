<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UC RUN CONTACT</title>
    <link rel="stylesheet" href="{{ asset('contact.css') }}">
    <script src="{{ asset('contact.js') }}" defer></script>
</head>
<body>
    <header>
    </header>

    <main class="contact-layout">
        <section class="title">
            <div class="logo">LOGO</div>
        </section>

        <div class="contact-panel">
            <h2>Contact Us</h2>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis.</p>

            <form id="contactForm" action="#" method="POST" class="contact-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-input" placeholder="Enter your name" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input" placeholder="you@example.com" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="message" class="form-label">Message</label>
                    <textarea id="message" name="message" row="4" class="form-textarea" placeholder="How can we help you?" required></textarea>
                </div>
                <button type="submit" class="submit-btn">SUBMIT</button>
                </div>
            </form>
        </div>

        <div class="image-placeholder">
            <span>GAMBAR</span>
        </div>
    </main>

    <section class="connect-layout">
        <h2>More Ways to Connect</h2>
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis, a tempus nisi tristique id.</p>

        <div class="connect-grid">
            <div class="connect-item">
                <div class="circle-icon"></div>
                <span class="icon-label">TEST</span>
            </div>
            <div class="connect-item">
                <div class="circle-icon"></div>
                <span class="icon-label">TEST</span>
            </div>
            <div class="connect-item">
                <div class="circle-icon"></div>
                <span class="icon-label">TEST</span>
            </div>
            <div class="connect-item">
                <div class="circle-icon"></div>
                <span class="icon-label">TEST</span>
            </div>
        </div>
    </section>    
</body>
</html>