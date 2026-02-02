// DOM Content Loaded event to make sure JS runs after the page is fully loaded
document.addEventListener('DOMContentLoaded', function () {
  
    // 1. Smooth scrolling for navigation links
    const scrollLinks = document.querySelectorAll('a[href^="#"]');
    scrollLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = link.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 50,  // Scroll to element with some offset
                    behavior: 'smooth'
                });
            }
        });
    });

    // 2. Form Validation (Example for Contact Form)
    const contactForm = document.querySelector('form');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            let valid = true;
            const name = contactForm.querySelector('#name').value.trim();
            const email = contactForm.querySelector('#email').value.trim();
            const message = contactForm.querySelector('#message').value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;  // Basic email validation

            // Validate name
            if (name === '') {
                alert('Name is required.');
                valid = false;
            }

            // Validate email
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address.');
                valid = false;
            }

            // Validate message
            if (message === '') {
                alert('Message cannot be empty.');
                valid = false;
            }

            // If form is not valid, prevent submission
            if (!valid) {
                e.preventDefault();  // Prevent form from submitting if invalid
            }
        });
    }

    // 3. Handle the "Get Started" button interaction (Example)
    const getStartedButton = document.querySelector('.btn');
    if (getStartedButton) {
        getStartedButton.addEventListener('click', function() {
            alert('Thanks for getting started! We will take you to the registration page.');
        });
    }

    // 4. Carousel/Slider logic (For Featured Mechanics, for example)
    const mechanicCards = document.querySelectorAll('.mechanic-card');
    let currentIndex = 0;
    const totalMechanics = mechanicCards.length;

    // Next Mechanic
    const nextMechanic = () => {
        mechanicCards[currentIndex].style.display = 'none'; // Hide current card
        currentIndex = (currentIndex + 1) % totalMechanics; // Loop back to the first card if at the end
        mechanicCards[currentIndex].style.display = 'block'; // Show next card
    };

    // Show the first mechanic on page load
    if (mechanicCards.length > 0) {
        mechanicCards[currentIndex].style.display = 'block';  // Show first mechanic card initially
        setInterval(nextMechanic, 3000);  // Change mechanic every 3 seconds (optional)
    }

});

// 5. Toggle mobile navigation menu (if using a mobile-friendly menu)
const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
if (mobileMenuToggle) {
    const navMenu = document.querySelector('nav ul');
    mobileMenuToggle.addEventListener('click', function() {
        navMenu.classList.toggle('open');
    });
}
