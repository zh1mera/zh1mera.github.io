document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects for unlocked levels
    const levelCards = document.querySelectorAll('.level-card.unlocked');
    levelCards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
        });
    });

    // Handle form submission for answers
    const answerForm = document.querySelector('#answerForm');
    if (answerForm) {
        answerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('validate_answer.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success && data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500); // Redirect after 1.5 seconds so user can see the success message
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting your answer.');
            });
        });
    }
});
