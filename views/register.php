<?php if(!isset($installed)||!$installed||$user){http_response_code(403);exit;} ?>
<p class="muted">New accounts start as New Employee. Your administrator manages the category.</p><form method="post">
<?= csrf() ?><input type="hidden" name="action" value="register">
<label>Full Name<input name="name" maxlength="100" autocomplete="name" value="<?= e(field('name')) ?>" required></label>
<small class="field-hint">Enter your full name.</small>
<label>Username<input name="username" minlength="3" maxlength="30" pattern="[A-Za-z0-9_.\-]{3,30}" autocomplete="username" autocapitalize="none" spellcheck="false" value="<?= e(field('username')) ?>" required></label>
<small class="field-hint">3–30 letters, numbers, dots, hyphens, or underscores.</small>
<label>Email Address<input type="email" name="email" maxlength="190" autocomplete="email" value="<?= e(field('email')) ?>" required></label>
<small class="field-hint">Include @ and a domain.</small>
<?php $personnelKind=false;include __DIR__.'/personnel-fields.php'; ?>
<label>Password<input type="password" name="password" minlength="8" maxlength="16" autocomplete="new-password" required></label>
<small class="field-hint">8–16 characters. Special characters are optional.</small>
<label>Confirm Password<input type="password" name="confirm_password" minlength="8" maxlength="16" autocomplete="new-password" required></label>
<small class="field-hint">Enter the same password.</small>
<button class="button full">Create Employee Account</button>
</form>
<p class="muted footnote">Already registered? <a class="text-link" href="index.php?auth=login">Sign In</a></p>
