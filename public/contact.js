document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('contactForm');

  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();

      // Retrieve field values
      const fullName = document.getElementById('full_name')?.value;
      const email = document.getElementById('email')?.value;
      const message = document.getElementById('message')?.value;

      console.log('Form Submitted Successfully:', {
        fullName,
        email,
        message,
      });

      alert('Thank you for contacting us! Your message has been submitted.');

      // Reset form fields
      form.reset();
    });
  }
});