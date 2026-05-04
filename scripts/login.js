
  //Lecture des paramètres dans l'URL.

  const params = new URLSearchParams(window.location.search);

  const error = params.get("error");
  const success = params.get("success");

  const messageBox = document.getElementById("login-message");

  //Messages d'erreur possibles.
 
  const errorMessages = {
    empty: "Veuillez remplir tous les champs.",
    not_found: "Utilisateur inexistant. Veuillez créer un compte.",
    wrong_password: "Mot de passe incorrect.",
    server: "Erreur serveur. Veuillez réessayer plus tard.",
    login_required: "Vous devez vous connecter pour accéder à cette page."
  };

  //Messages de succès possibles.

  const successMessages = {
    registered: "Compte créé avec succès. Vous pouvez maintenant vous connecter.",
    logout: "Vous avez été déconnecté avec succès."
  };

  // Affichage du message d'erreur.
  
  if (error && errorMessages[error]) {
    messageBox.textContent = errorMessages[error];
    messageBox.style.display = "block";
    messageBox.classList.add("error");
  }

  // Affichage du message de succès.
  
  if (success && successMessages[success]) {
    messageBox.textContent = successMessages[success];
    messageBox.style.display = "block";
    messageBox.classList.add("success");
  }


const successMessages = {
  logout: "Vous avez été déconnecté avec succès."
};
