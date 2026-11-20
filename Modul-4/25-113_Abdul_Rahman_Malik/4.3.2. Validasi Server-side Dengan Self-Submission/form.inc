<?php
// form.inc
// isinya tampilan form (dipisah dari proses datanya)

// langkah 7: ambil isian sebelumnya biar gak ilang pas form muncul lagi
// kalau belum ada isian (pertama kali buka), nilainya kosong
// htmlspecialchars biar aman dari script yang diselipin user
$surname    = isset($_POST['surname'])  ? htmlspecialchars($_POST['surname']) : '';
$email      = isset($_POST['email'])    ? htmlspecialchars($_POST['email'])   : '';
$address    = isset($_POST['address'])  ? htmlspecialchars($_POST['address']) : '';
$state      = isset($_POST['state'])    ? $_POST['state'] : '-1';
$sex        = isset($_POST['sex'])      ? $_POST['sex'] : '';
$vegetarian = isset($_POST['vegetarian']);
// password sengaja gak diisi ulang, biar lebih aman

// daftar state, dipakai buat bikin dropdown
$states = array(
    '1' => 'Australian Capital Territory',
    '2' => 'Queensland',
    '3' => 'Victoria',
    '4' => 'New South Wales',
    '5' => 'Tasmania',
    '6' => 'South Australia',
    '7' => 'Western Australia',
    '8' => 'Northern Territory'
);
?>
<style>
	fieldset { background-color: #dcdcff; width: 420px; }
</style>
<h1>Register</h1>
<fieldset>
	<legend>Person Details</legend>
		<!-- action ke file ini sendiri (self-submission) -->
		<form name="myForm" action="processData_form.php" method="POST">
			<div class="field">
				<label for="surname">Surname</label>
				<!-- value diisi data sebelumnya -->
				<input type="text" name="surname" size="31" value="<?php echo $surname; ?>"/>
			</div>
			<div class="field">
				<label for="email">Email address</label>
				<input type="text" name="email" size="31" value="<?php echo $email; ?>"/>
			</div>
			<div class="field">
				<label for="passwd">Password</label>
				<input type="password" name="passwd" size="31"/>
			</div>
			<div class="field">
				<label for="address">Street Address</label>
				<!-- textarea gak pakai value, isinya ditaruh di antara tag -->
				<textarea name="address" rows="3" cols="22"><?php echo $address; ?></textarea>
			</div>
			<div class="field">
				<label for="state">State</label>
				<select name="state">
					<option value="-1" <?php if ($state == '-1') echo 'selected'; ?>>[choose your state]</option>
					<?php
					// bikin option satu-satu, yang cocok sama pilihan sebelumnya dikasih selected
					foreach ($states as $val => $nama)
					{
						$sel = ($state == $val) ? 'selected' : '';
						echo "<option value=\"$val\" $sel>$nama</option>";
					}
					?>
				</select>
			</div>
			<div class="field">
				<input type="hidden" name="country" value="Australia"/>
			</div>
			<div class="field">
				<label for="sex">Gender</label>
				<input type="radio" name="sex" value="male" <?php if ($sex == 'male') echo 'checked'; ?>/>Male
				<input type="radio" name="sex" value="female" <?php if ($sex == 'female') echo 'checked'; ?>/>Female
			</div>
			<div class="field">
				<label for="vegetarian">Vegetarian?</label>
				<input type="checkbox" name="vegetarian" <?php if ($vegetarian) echo 'checked'; ?>/>
			</div>
			<div class="field">
				<label>&nbsp;</label>
				<input type="submit" value="Submit" name="submit"/><input type="reset" value="Reset" name="reset"/>
			</div>
		</form>
</fieldset>