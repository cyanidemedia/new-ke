<?php
/**
 * Checks the cart for either an iPad enclosure, or iPad mount
 * and reminds the user they need the other part for a complete
 * set.  If both exist in the cart, it does not display a message.
 *
 * @author Jonathon McDonald <jon@onewebcentric.com>
 */

/**
 * Checks if the users current cart session contains an
 * iPad enclosure.  
 *
 * @return bool True if the cart does contain an iPad enclosure
 */
function doesCartContainIpadEnclosure() {
	return false;
}

/**
 * Checks if the current cart session contains an iPad mount.
 *
 * @return bool True if the cart does contain an iPad mount
 */
function doesCartContainIpadMount() {
	return false;
}