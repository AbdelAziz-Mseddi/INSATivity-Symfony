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

    // Lecture des paramètres dans l'URL.
    const params = new URLSearchParams(window.location.search);
    const error = params.get("error");
    const success = params.get("success");

    // Messages d'erreur possibles.
    const errorMessages = {
        empty: "Veuillez remplir tous les champs.",
        not_found: "Utilisateur inexistant. Veuillez créer un compte.",
        wrong_password: "Mot de passe incorrect.",
        server: "Erreur serveur. Veuillez réessayer plus tard.",
        login_required: "Vous devez vous connecter pour accéder à cette page."
    };

    // Messages de succès possibles.
    const successMessages = {
        registered: "Compte créé avec succès. Vous pouvez maintenant vous connecter.",
        logout: "Vous avez été déconnecté avec succès."
    };

    // Affichage du message d'erreur.
    if (error && errorMessages[error]) {
        showMessage(errorMessages[error], true);
    }

    // Affichage du message de succès.
    if (success && successMessages[success]) {
        showMessage(successMessages[success], false);
    }

    if (loginForm) {
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
                    window.location.href = "index.html";
                }, 1000);
            } catch (err) {
                showMessage(err.message || "Erreur serveur. Veuillez réessayer plus tard.");
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }
});
