
  const params = new URLSearchParams(window.location.search);
  const error = params.get("error");

  const messageBox = document.getElementById("register-message");

  const errorMessages = {
    full_name_required: "Veuillez saisir votre nom complet.",
    username_required: "Veuillez saisir un nom d'utilisateur.",
    username_too_short: "Le nom d'utilisateur doit contenir au moins 3 caractères.",
    username_exists: "Ce nom d'utilisateur existe déjà.",
    email_required: "Veuillez saisir votre email universitaire.",
    email_invalid: "Email invalide.",
    email_domain_invalid: "L'email doit appartenir au domaine @insat.ucar.tn.",
    email_exists: "Cet email est déjà utilisé.",
    major_required: "Veuillez choisir votre filière.",
    major_invalid: "Filière invalide.",
    password_required: "Veuillez saisir un mot de passe.",
    password_too_short: "Le mot de passe doit contenir au moins 6 caractères.",
    confirm_password_required: "Veuillez confirmer le mot de passe.",
    passwords_not_match: "Les deux mots de passe ne correspondent pas.",
    terms_required: "Vous devez accepter les conditions.",
    already_exists: "Un compte existe déjà avec ces informations.",
    server: "Erreur serveur. Veuillez réessayer plus tard."
  };

  if (error && errorMessages[error]) {
    messageBox.textContent = errorMessages[error];
    messageBox.style.display = "block";
    messageBox.classList.add("error");
  }