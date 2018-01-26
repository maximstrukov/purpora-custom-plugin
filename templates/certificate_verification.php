<h1>Verify Certificate Authenticity</h1>
<form action="" method="POST" id="certificate_verification_form">
	<table>
		<tbody>
			<tr>
				<td>
					<input style="border: 3px solid;" type="text" name="cert_verifier" size="30" placeholder="Certificate ID" />
					<img src="<?php echo get_template_directory_uri(); ?>/images/loading.gif"/>
					<div class="certificate_message valid">
						<div class="result">Certificate is <strong>VALID</strong></div>
						<div class="username">User: <strong></strong></div>
						<div class="exp_date">Exp. date: <strong></strong></div>
					</div>
					<div class="certificate_message invalid">Certificate is <strong>INVALID</strong></div>
				</td>
			</tr>
		</tbody>
	</table>
	<input type="submit" />
</form>