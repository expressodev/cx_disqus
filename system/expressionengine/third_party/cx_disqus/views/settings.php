<?php if ($display_welcome): ?>
	<div style="background-color: #cfc; padding: 10px; margin-bottom: 10px; border-radius: 5px; border: 2px solid #6f6;">
		<h3>Thanks for installing CX Disqus Comments!</h3>
		To get started using Disqus comments on your ExpressionEngine site, please enter your
		<strong>Forum Short Name</strong> and <strong>API Secret Key</strong> below.
	</div>
<?php elseif ($error_code > 0): ?>
	<div style="background-color: #fcc; padding: 10px; margin-bottom: 10px; border-radius: 5px; border: 2px solid #f66;">
		<p><strong>There was a problem communicating with Disqus!</strong></p>
		<p>Error <?= $error_code ?>: <?= $error_message ?></p>
		<?= lang('api_error_more_info') ?>
	</div>
<?php else: ?>
	<div style="background-color: #cfc; padding: 10px; margin-bottom: 10px; border-radius: 5px; border: 2px solid #6f6;">
		<p><strong>Everything is configured correctly!</strong></p>
		Communication with the Disqus API appears to be functioning normally.
	</div>
<?php endif; ?>

<?= form_open($post_url); ?>

<?php
	$this->table->clear();
	$this->table->set_template($cp_table_template);
	$this->table->set_heading(
		array('data' => lang('preference'), 'width' => "50%"),
		array('data' => lang('setting')));

	$this->table->add_row(
		'<strong>'.lang('forum_shortname').'</strong>'.BR.
		'<div class="subtext">'.lang('forum_shortname_desc').'</div>',
		form_input('forum_shortname', $forum_shortname)
	);

	$this->table->add_row(
		'<strong>'.lang('secretkey').'</strong>'.BR.
		'<div class="subtext">'.lang('secretkey_desc').'</div>',
		form_input('secretkey', $secretkey)
	);

	$this->table->add_row(
		'<strong>'.lang('advanced').'</strong>',
		'<label>'.form_checkbox('reset_import', 'y').' '.lang('reset_import').'</label>'.BR.
		'<div class="subtext">'.lang('reset_import_desc').'</div>'
	);

	echo $this->table->generate();
?>

<div style="text-align: right;">
	<?= form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit')); ?>
</div>

<?= form_close(); ?>