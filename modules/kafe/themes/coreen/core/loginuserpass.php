<?php 
	$this->data['layout-header'] = true;
	$this->includeAtTemplateBase('includes/header-coreen.php');
?>
<!-- layout-container -->
<div id="layout-container">
	<div id="container">
		<!-- container-body -->
		<div id="container-body">
			<div id="contents">
				<div class="box-kreonet login-form">
					<form action="?" method="post" name="f">
					<?php
					foreach ($this->data['stateparams'] as $name => $value) {
						echo('<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" />');
					}
					?>
					<p class="img">
						<img src="/<?php echo $this->data['baseurlpath']; ?>resources/coreen/images/kreonet_logo.gif" alt="kreonet" />
					</p>
					<ul>
						<li>
						<?php
						if ($this->data['forceUsername']) {
							echo '<strong>' . htmlspecialchars($this->data['username']) . '</strong>';
						} else {
							echo '<input type="text" placeholder="Username" name="username" value="' . htmlspecialchars($this->data['username']) . '" />';
						}
						?>				
						</li>
						<li><input type="password" autocomplete="off" name="password" placeholder="Password" /></li>
					</ul>
					<input type="submit" class="btn-purple" value="Login" />
					</form>
				</div>

				<div class="box-blue">
					<h2>Please note</h2>
					<p class="content">Before entering your username and password, <br />verify that the URL for this page begins with: <a href="https://www.your.org" target="_blank">https://www.your.org</a></p>
				</div>

				<?php if ($this->data['errorcode'] !== NULL) { ?>
				<p class="icon-wrong">
					<?php 
					if ($this->data['errorcode'] == 'WRONGUSERPASS') {
						echo 'Either no user with the given username could be found, or the password you gave was wrong. <br />
					Your IP will be blocked after 5 login failures ('.(5-@$_SESSION['try_times']).' times left). <br />Please check your username/password and try again. ';
					} else {
						echo htmlspecialchars($this->t('{errors:descr_' . $this->data['errorcode'] . '}', $this->data['errorparams'])); 
					}
					?>
				</p>
				<?php } ?>

				<p class="newuser">
					New user? or forgot your password?
					<span>Go to <a href="https://coreen-idp.kreonet.net" target="_blank">KAFE IMS</a></span>
				</p>
			</div>
		</div>
		<!-- //container-body -->
	</div>
</div>
<!-- //layout-container -->

<?php 
	$this->includeAtTemplateBase('includes/footer-coreen.php');
?>
