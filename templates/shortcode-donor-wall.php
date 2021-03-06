<?php
/**
 * This template is used to display the donation grid with [give_donor_wall]
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$donation = $args[0];

$give_settings = $args[1]; // Give settings.
$atts          = $args[2]; // Shortcode attributes.
?>

<div class="give-grid__item">
	<div class="give-donor give-card">
		<div class="give-donor__header">
			<?php
			if( true === $atts['show_avatar'] ) {
				$has_avatar = absint( give_validate_gravatar( $donation['_give_payment_donor_email'] ) );
				// Maybe display the Avatar.
				echo sprintf(
					'<div class="give-donor__image" data-donor_email="%1$s" data-has-valid-gravatar="%2$s">%3$s</div>',
					md5( strtolower( trim( $donation['_give_payment_donor_email'] ) ) ),
					$has_avatar,
					defined( 'REST_REQUEST' ) && $has_avatar
						? get_avatar($donation['_give_payment_donor_email'])
						: $donation['name_initial']
				);
			}
			?>

			<div class="give-donor__details">
				<?php if ( true === $atts['show_name'] ) : ?>
					<h3 class="give-donor__name">
						<?php $donor_name = trim( $donation['_give_donor_billing_first_name'] . ' ' . $donation['_give_donor_billing_last_name'] ); ?>
						<?php echo esc_html( $donor_name ); ?>
					</h3>
				<?php endif; ?>

				<?php if ( true === $atts['show_total'] ) : ?>
					<span class="give-donor__total">
						<?php echo esc_html( give_donation_amount( $donation['donation_id'], true ) ); ?>
					</span>
				<?php endif; ?>

				<?php if ( true === $atts['show_time'] ) : ?>
					<span class="give-donor__timestamp">
						<?php echo esc_html( give_get_formatted_date( $donation[ 'donation_date' ], give_date_format(), 'Y-m-d H:i:s' ) ); ?>
					</span>
				<?php endif; ?>
			</div>
		</div>

		<?php
		if (
			true === $atts['show_comments']
			&& absint( $atts['comment_length'] )
			&& ! empty( $donation['donor_comment'] )
		) :
			?>
			<div class="give-donor__content">
				<?php
				$comment     = trim( $donation['donor_comment'] );
				$total_chars = strlen( $comment );
				$max_chars   = $atts['comment_length'];

				// A truncated excerpt is displayed if the comment is too long.
				if ( $max_chars < $total_chars ) {
					$excerpt    = '';
					$offset     = -( $total_chars - $max_chars );
					$last_space = strrpos( $comment, ' ', $offset );

					if ( $last_space ) {
						// Truncate excerpt at last space before limit.
						$excerpt = substr( $comment, 0, $last_space );
					} else {
						// There are no spaces, so truncate excerpt at limit.
						$excerpt = substr( $comment, 0, $max_chars );
					}

					$excerpt = trim( $excerpt, '.!,:;' );

					echo sprintf(
						'<p class="give-donor__excerpt">%s&hellip;<span> <a class="give-donor__read-more">%s</a></span></p>',
						nl2br( esc_html( $excerpt ) ),
						esc_html( $atts['readmore_text'] )
					);
				}

				echo sprintf(
					'<p class="give-donor__comment">%s</p>',
					nl2br( esc_html( $comment ) )
				);
				?>
			</div>
		<?php endif; ?>
	</div>
</div>
