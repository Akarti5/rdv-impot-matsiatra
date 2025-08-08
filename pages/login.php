<section class="auth">
  <div class="auth-card">
    <h2>Connexion</h2>
    <form id="login-form" class="form">
      <input type="hidden" name="_csrf" value="<?= s(csrf_token()) ?>">
      <div class="form-group">
        <label for="login-email">Email</label>
        <input id="login-email" name="email" type="email" placeholder="email@exemple.com" required>
      </div>
      <div class="form-group">
        <label for="login-password">Mot de passe</label>
        <input id="login-password" name="password" type="password" placeholder="********" required minlength="8">
      </div>
      <button type="submit" class="btn btn-full">Se connecter</button>
      <div class="form-meta">
        <a href="#" id="password-reset-link">Mot de passe oublié ?</a>
      </div>
      <div id="login-message" class="form-message" aria-live="polite"></div>
    </form>
  </div>
</section>
