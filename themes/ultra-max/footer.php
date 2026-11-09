<?php
/**
 * Site footer.
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

?>
</main>
<footer class="um-scope um-site-footer">
	<div class="mx-auto max-w-(--um-container) px-4 py-6">
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'footer',
				'container'      => 'nav',
				'fallback_cb'    => false,
				'depth'          => 1,
			)
		);
		?>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
