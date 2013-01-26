<?php if ($comment_export_count == 0): ?>

	<p>You have successfully exported all of your comments to Disqus!</p>

<?php else: ?>

	<p><?= $comment_export_count ?> comments will be exported to Disqus</p>

	<p><strong>Please ensure:</strong></p>

	<ul class="bullets">
		<li><strong>You have given your API application write access to your forum</strong>, and</li>
		<li><strong>Your API application authentication is set to "Inherit permissions"</strong></li>
	</ul>

	<p><a href="http://disqus.com/api/applications/">http://disqus.com/api/applications/</a></p>

	<?= form_open($post_url); ?>
		<div style="text-align: right;">
			<?= form_submit(array('name' => 'submit', 'value' => lang('export_comments'), 'class' => 'submit')); ?>
		</div>
	<?= form_close(); ?>

<?php endif; ?>