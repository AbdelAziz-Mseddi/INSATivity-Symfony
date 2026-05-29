import { API } from './api.js';

document.addEventListener("DOMContentLoaded", () => {
    const messageBox = document.getElementById("login-message");
    const loginForm = document.querySelector(".login-form");

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

    loginForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        
        const username = loginForm.username.value.trim();
        const password = loginForm.password.value;

        if (!username || !password) {
            showMessage("Veuillez remplir tous les champs.");
            return;
        }

        const submitBtn = loginForm.querySelector("button[type='submit']");
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = "Logging in...";

        try {
            await API.login(username, password);
            showMessage("Login successful! Redirecting...", false);
            setTimeout(() => {
                window.location.href = "/";
            }, 1000);
        } catch (error) {
            showMessage(error.message || "Erreur serveur. Veuillez réessayer plus tard.");
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
});
