import { API } from './api.js';

document.addEventListener("DOMContentLoaded", () => {
    const messageBox = document.getElementById("register-message");
    const registerForm = document.querySelector(".login-form");

    function showMessage(msg, isError = true) {
        messageBox.textContent = msg;
        messageBox.style.display = "block";
        if (isError) {
            messageBox.classList.add("error");
            messageBox.classList.remove("success");
        } else {
            messageBox.classList.add("success");
            messageBox.classList.remove("error");
        }
    }

    registerForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        
        const fullName = registerForm.fullName.value.trim();
        const username = registerForm.username.value.trim();
        const email = registerForm.email.value.trim();
        const major = registerForm.major.value;
        const password = registerForm.password.value;
        const confirmPassword = registerForm.confirmPassword.value;
        const acceptTerms = registerForm.acceptTerms.checked;

        if (!fullName || !username || !email || !major || !password || !confirmPassword) {
            showMessage("Veuillez remplir tous les champs.");
            return;
        }

        if (password !== confirmPassword) {
            showMessage("Les deux mots de passe ne correspondent pas.");
            return;
        }

        if (!acceptTerms) {
            showMessage("Vous devez accepter les conditions.");
            return;
        }

        const submitBtn = registerForm.querySelector("button[type='submit']");
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = "Creating account...";

        try {
            await API.register({ fullName, username, email, major, password });
            showMessage("Account created! Redirecting...", false);
            setTimeout(() => {
                window.location.href = "index.html";
            }, 1000);
        } catch (error) {
            showMessage(error.message || "Erreur serveur. Veuillez réessayer plus tard.");
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
});