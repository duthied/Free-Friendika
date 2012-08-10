<?php
if(class_exists('Item'))
	return;

require_once('include/text.php');

/**
 * An item
 */
class Item {
	private $data = array();

	public function __construct($data) {
		$this->data = $data;
	}

	/**
	 * Get the item ID
	 *
	 * Returns:
	 * 		_ the ID on success
	 * 		_ false on failure
	 */
	public function get_id() {
		if(!x($this->data['id'])) {
			logger('[ERROR] Item::get_id : Item has no ID!!', LOGGER_DEBUG);
			return false;
		}

		return $this->data['id'];
	}

	/**
	 * Get data in a form usable by a conversation template
	 *
	 * Returns:
	 * 		_ The data requested on success
	 * 		_ false on failure
	 */
	public function get_template_data() {
		$result = array();

		

		return $result;
	}

	/**
	 * Get raw data
	 *
	 * We shouldn't need this
	 */
	public function get_data() {
		return $this->data;
	}
}
?>
