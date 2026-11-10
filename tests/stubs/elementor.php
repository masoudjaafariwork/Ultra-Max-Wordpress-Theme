<?php
/**
 * Signatures of the Elementor classes Ultra Max uses, for PHPStan only.
 *
 * Written for this project from Elementor's public API; never loaded at
 * runtime. Only the members our code calls are declared.
 *
 * @package UltraMax\Tests
 */

// phpcs:ignoreFile

namespace Elementor {

	class Plugin {
		/** @var Plugin */
		public static $instance;
		/** @var Frontend */
		public $frontend;
		/** @var Core\Documents_Manager */
		public $documents;
		/** @var Core\Page_Assets\Loader */
		public $assets_loader;
		/** @var Widgets_Manager */
		public $widgets_manager;
		/** @var Controls_Manager */
		public $controls_manager;
		/** @var Core\Kits\Manager */
		public $kits_manager;
		/** @var Preview */
		public $preview;
		/** @var Editor */
		public $editor;
		/** @return Plugin */
		public static function instance() {}
	}

	class Preview {
		/** @return bool */
		public function is_preview_mode( $post_id = 0 ) {}
	}

	class Editor {
		/** @return bool */
		public function is_edit_mode( $post_id = null ) {}
	}

	class Frontend {
		/** @return string */
		public function get_builder_content_for_display( $post_id, $with_css = false ) {}
		/** @return void */
		public function enqueue_styles() {}
	}

	class Widgets_Manager {
		/** @return bool */
		public function register( Widget_Base $widget_instance ) {}
	}

	class Elements_Manager {
		/** @return void */
		public function add_category( $category_name, $category_properties ) {}
	}

	class Controls_Manager {
		const TAB_CONTENT = 'content';
		const TAB_STYLE   = 'style';
		const TEXT        = 'text';
		const TEXTAREA    = 'textarea';
		const WYSIWYG     = 'wysiwyg';
		const CODE        = 'code';
		const URL         = 'url';
		const MEDIA       = 'media';
		const SELECT      = 'select';
		const SELECT2     = 'select2';
		const SWITCHER    = 'switcher';
		const NUMBER      = 'number';
		const COLOR       = 'color';
		const REPEATER    = 'repeater';
		const DATE_TIME   = 'date_time';
		const HEADING     = 'heading';
		/** @return void */
		public function register( Base_Control $control_instance, $control_id = null ) {}
	}

	abstract class Base_Control {
		/** @return string */
		abstract public function get_type();
		/** @return void */
		public function print_control_uid( $input_type = 'default' ) {}
	}

	abstract class Base_Data_Control extends Base_Control {
		/** @return array<string, mixed> */
		protected function get_default_settings() {}
		/** @return void */
		abstract public function content_template();
	}

	class Controls_Stack {
		/** @return void */
		public function start_controls_section( $section_id, array $args = [] ) {}
		/** @return void */
		public function end_controls_section() {}
		/** @return bool */
		public function add_control( $id, array $args, $options = [] ) {}
		/** @return array<string, mixed> */
		public function get_controls( $control_id = null ) {}
		/** @return mixed */
		public function get_settings_for_display( $setting_key = null ) {}
		/** @return mixed */
		public function get_settings( $setting = null ) {}
		/** @return string */
		public function get_id() {}
	}

	class Repeater extends Controls_Stack {
	}

	abstract class Element_Base extends Controls_Stack {
		/** @return array<string, mixed>|mixed */
		public function get_default_args( $item = null ) {}
	}

	abstract class Widget_Base extends Element_Base {
		/**
		 * @param array<mixed>      $data Element data.
		 * @param array<mixed>|null $args Default arguments.
		 */
		public function __construct( $data = [], $args = null ) {}
		/** @return string */
		public function get_name() {}
		/** @return string */
		public function get_title() {}
		/** @return string */
		public function get_icon() {}
		/** @return list<string> */
		public function get_categories() {}
		/** @return list<string> */
		public function get_keywords() {}
		/** @return list<string> */
		public function get_style_depends(): array {}
		/** @return list<string> */
		public function get_script_depends(): array {}
		/** @return bool */
		public function has_widget_inner_wrapper(): bool {}
		/** @return bool */
		protected function is_dynamic_content(): bool {}
		/** @return void */
		protected function register_controls() {}
		/** @return void */
		protected function render() {}
	}
}

namespace Elementor\Core\Base {
	abstract class Document {
		/** @return string */
		public function get_edit_url() {}
		/** @return int */
		public function get_main_id() {}
		/** @return mixed */
		public function get_settings( $key = null ) {}
		/** @return bool */
		public function save( $data ) {}
	}
}

namespace Elementor\Core {
	class Documents_Manager {
		/** @return Base\Document|false */
		public function get( $post_id, $from_cache = true ) {}
	}
}

namespace Elementor\Core\Files\CSS {
	class Post {
		/** @return static */
		public static function create( $post_id ) {}
		/** @return void */
		public function enqueue() {}
		/** @return void */
		public function delete() {}
	}
}

namespace Elementor\Core\Page_Assets {
	class Loader {
		/** @param array<string, list<string>> $assets_data */
		public function enable_assets( array $assets_data ): void {}
	}
}

namespace Elementor\Core\Kits {
	class Manager {
		/** @return Documents\Kit|false */
		public function get_active_kit() {}
		/** @return int */
		public function get_active_id() {}
	}
}

namespace Elementor\Core\Kits\Documents {
	class Kit extends \Elementor\Core\Base\Document {
		/** @return array<string, mixed> */
		public function get_settings( $key = null ) {}
	}
}

namespace Elementor\Core\DynamicTags {
	abstract class Base_Tag extends \Elementor\Controls_Stack {
		/** @return string */
		abstract public function get_name();
		/** @return string */
		abstract public function get_title();
		/** @return string */
		abstract public function get_group();
		/** @return list<string> */
		abstract public function get_categories();
	}

	abstract class Tag extends Base_Tag {
		/** @return void */
		abstract public function render();
	}

	abstract class Data_Tag extends Base_Tag {
		/** @return mixed */
		abstract protected function get_value( array $options = [] );
	}

	class Manager {
		/** @return void */
		public function register( Base_Tag $dynamic_tag_instance ) {}
		/** @return void */
		public function register_group( $group_name, array $group_settings ) {}
	}
}

namespace Elementor\Modules\DynamicTags {
	class Module {
		const TEXT_CATEGORY   = 'text';
		const URL_CATEGORY    = 'url';
		const IMAGE_CATEGORY  = 'image';
		const NUMBER_CATEGORY = 'number';
		const POST_META_CATEGORY = 'post_meta';
	}
}
