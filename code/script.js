// script.js
document.getElementById('next-1').addEventListener('click', function() {
    const name = document.getElementById('name').value;
    const phone = document.getElementById('phone').value;
    const dob = new Date(document.getElementById('dob').value);
    const today = new Date();
    const age = today.getFullYear() - dob.getFullYear();
    
    if (!name || !phone || !dob || !document.getElementById('address').value || !document.getElementById('password').value || document.getElementById('password').value !== document.getElementById('confirm-password').value) {
      alert("Please fill in all fields and ensure the passwords match.");
      return;
    }
  
    if (age < 18) {
      alert("You must be at least 18 years old.");
      return;
    }
  
    document.getElementById('step-1').style.display = "none";
    document.getElementById('step-2').style.display = "block";
  });
  
  document.getElementById('next-2').addEventListener('click', function() {
    // Simulate email verification process
    alert("Email has been verified.");
    document.getElementById('step-2').style.display = "none";
    document.getElementById('step-3').style.display = "block";
  });
  
  document.getElementById('user-type').addEventListener('change', function() {
    const userType = this.value;
    const mechanicFields = document.getElementById('mechanic-fields');
    
    if (userType === "mechanic") {
      mechanicFields.style.display = "block";
    } else {
      mechanicFields.style.display = "none";
    }
  });
  
  document.getElementById('next-3').addEventListener('click', function() {
    // Proceed to the final confirmation step
    document.getElementById('step-3').style.display = "none";
    document.getElementById('step-4').style.display = "block";
  });
  
  document.getElementById('multiStepForm').addEventListener('submit', async function(event) {
    event.preventDefault();
    
    const formData = {
      name: document.getElementById('name').value,
      phone: document.getElementById('phone').value,
      dob: document.getElementById('dob').value,
      address: document.getElementById('address').value,
      password: document.getElementById('password').value,
      userType: document.getElementById('user-type').value,
      skills: document.getElementById('skills') ? document.getElementById('skills').value : null
    };
  
    const response = await fetch('/submit-form', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(formData)
    });
  
    const data = await response.json();
    if (data.success) {
      alert("Form submitted successfully!");
    } else {
      alert("There was an error. Please try again.");
    }
  });
  