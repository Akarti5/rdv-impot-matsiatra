<section class="auth">
  <div class="auth-card">
    <h2>Créer un compte</h2>
    <form id="register-form" class="form">
      <input type="hidden" name="_csrf" value="<?= s(csrf_token()) ?>">
      <div class="grid-2">
        <div class="form-group">
          <label for="reg-nom">Nom</label>
          <input id="reg-nom" name="nom" type="text" required maxlength="100">
        </div>
        <div class="form-group">
          <label for="reg-prenom">Prénom</label>
          <input id="reg-prenom" name="prenom" type="text" required maxlength="100">
        </div>
      </div>
      <div class="form-group">
        <label for="reg-email">Email</label>
        <input id="reg-email" name="email" type="email" required maxlength="150">
      </div>
      <div class="grid-2">
        <div class="form-group">
          <label for="reg-phone">Téléphone</label>
          <input id="reg-phone" name="phone" type="tel" maxlength="20" pattern="[0-9+ ]*">
        </div>
        <div class="form-group">
          <label for="reg-nif">NIF</label>
          <input id="reg-nif" name="nif" type="text" maxlength="20" pattern="[0-9A-Za-z\-]*">
        </div>
      </div>
      <div class="form-group">
        <label>Type de compte</label>
        <div class="radio-group">
          <label><input type="radio" name="type_user" value="client" checked> Contribuable / Client</label>
          <label><input type="radio" name="type_user" value="agent"> Agent des impôts</label>
        </div>
      </div>
      <div class="form-group">
        <label for="reg-password">Mot de passe</label>
        <input id="reg-password" name="password" type="password" required minlength="8" placeholder="Au moins 8 caractères">
      </div>
      <div class="form-group">
        <label for="reg-password2">Confirmer le mot de passe</label>
        <input id="reg-password2" name="password2" type="password" required minlength="8">
      </div>
      <button type="submit" class="btn btn-full">Créer mon compte</button>
      <div id="register-message" class="form-message" aria-live="polite"></div>
    </form>
  </div>
</section>
