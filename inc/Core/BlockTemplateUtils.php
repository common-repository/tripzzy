<?php
/**
 * Block Template Utils Class.
 *
 * @since 1.0.6
 * @package tripzzy
 */

namespace Tripzzy\Core;

/**
 * Utility methods used for serving block templates from Tripzzy Blocks.
 * {@internal This class and its methods should only be used within the BlockTemplates.php and is not intended for public use.}
 */
class BlockTemplateUtils {
	/**
	 * Directory names for block templates
	 *
	 * @var array {
	 *     @var string DEPRECATED_TEMPLATES  Old directory name of the block templates directory.
	 *     @var string DEPRECATED_TEMPLATE_PARTS  Old directory name of the block template parts directory.
	 *     @var string TEMPLATES_DIR_NAME  Directory name of the block templates directory.
	 *     @var string TEMPLATE_PARTS_DIR_NAME  Directory name of the block template parts directory.
	 * }
	 */
	const DIRECTORY_NAMES = array(
		'DEPRECATED_TEMPLATES'      => 'block-templates',
		'DEPRECATED_TEMPLATE_PARTS' => 'block-template-parts',
		'TEMPLATES'                 => 'templates',
		'TEMPLATE_PARTS'            => 'parts',
	);

	const TEMPLATES_ROOT_DIR = 'templates';

	/**
	 * Tripzzy plugin slug
	 *
	 * This is used to save templates to the DB which are stored against this value in the wp_terms table.
	 *
	 * @var string
	 */
	const PLUGIN_SLUG = 'tripzzy/tripzzy';

	/**
	 * Check whether the compatible WP or the gutenberg plugin installed or not.
	 *
	 * @param string $template_type Optional. Template type: `wp_template` or `wp_template_part`.
	 *                              Default `wp_template`.
	 * @since 1.0.6
	 * @return boolean
	 */
	public static function supports_block_templates( $template_type = 'wp_template' ) {
		if ( 'wp_template_part' === $template_type && ( tripzzy_is_fse_theme() || current_theme_supports( 'block-template-parts' ) ) ) {
			return true;
		} elseif ( 'wp_template' === $template_type && tripzzy_is_fse_theme() ) {
			return true;
		}
		return false;
	}
	/**
	 * Gets the directory where templates of a specific template type can be found.
	 *
	 * @param string $template_type wp_template or wp_template_part.
	 *
	 * @since 1.0.6
	 * @return string
	 */
	public static function get_templates_directory( $template_type = 'wp_template' ) {
		$root_path                = dirname( __DIR__, 2 ) . '/' . self::TEMPLATES_ROOT_DIR . DIRECTORY_SEPARATOR;
		$templates_directory      = $root_path . self::DIRECTORY_NAMES['TEMPLATES'];
		$template_parts_directory = $root_path . self::DIRECTORY_NAMES['TEMPLATE_PARTS'];

		if ( 'wp_template_part' === $template_type ) {
			return $template_parts_directory;
		}

		return $templates_directory;
	}
	/**
	 * Finds all nested template part file paths in base directory.
	 *
	 * @param string $base_directory The theme's file path.
	 * @since 1.0.6
	 * @return array $path_list A list of paths to all template part files.
	 */
	public static function get_template_paths( $base_directory ) {
		$path_list = array();
		if ( file_exists( $base_directory ) ) {
			$nested_files      = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $base_directory ) );
			$nested_html_files = new \RegexIterator( $nested_files, '/^.+\.html$/i', \RecursiveRegexIterator::GET_MATCH );
			foreach ( $nested_html_files as $path => $file ) {
				$path_list[] = $path;
			}
		}
		return $path_list;
	}


	/**
	 * Gets the templates saved in the database.
	 *
	 * @param array  $slugs An array of slugs to retrieve templates for.
	 * @param string $template_type wp_template or wp_template_part.
	 *
	 * @since 1.0.6
	 * @return int[]|\WP_Post[] An array of found templates.
	 */
	public static function get_block_templates_from_db( $slugs = array(), $template_type = 'wp_template' ) {
		$check_query_args = array(
			'post_type'      => $template_type,
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'wp_theme',
					'field'    => 'name',
					'terms'    => array( self::PLUGIN_SLUG, get_stylesheet() ),
				),
			),
		);

		if ( is_array( $slugs ) && count( $slugs ) > 0 ) {
			$check_query_args['post_name__in'] = $slugs;
		}

		$check_query             = new \WP_Query( $check_query_args );
		$saved_tripzzy_templates = $check_query->posts;

		return array_map(
			function ( $saved_tripzzy_template ) {
				return self::build_template_result_from_post( $saved_tripzzy_template );
			},
			$saved_tripzzy_templates
		);
	}

	/**
	 * Build a page template object based on a post Object.
	 * Important: This method is an almost identical duplicate from wp-includes/block-template-utils.php. It is modified to get all plugin templates.
	 *
	 * @param \WP_Post $post Template post.
	 *
	 * @since 1.0.6
	 * @return \WP_Block_Template|\WP_Error Template.
	 */
	public static function build_template_result_from_post( $post ) {
		$terms = get_the_terms( $post, 'wp_theme' );

		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		if ( ! $terms ) {
			return new \WP_Error( 'template_missing_theme', __( 'No theme is defined for this template.', 'tripzzy' ) );
		}

		$theme          = $terms[0]->name;
		$has_theme_file = true;

		$template                 = new \WP_Block_Template();
		$template->wp_id          = $post->ID;
		$template->id             = $theme . '//' . $post->post_name;
		$template->theme          = $theme;
		$template->content        = $post->post_content;
		$template->slug           = $post->post_name;
		$template->source         = 'custom';
		$template->type           = $post->post_type;
		$template->description    = $post->post_excerpt;
		$template->title          = $post->post_title;
		$template->status         = $post->post_status;
		$template->has_theme_file = $has_theme_file;
		$template->is_custom      = false;
		$template->post_types     = array(); // Don't appear in any Edit Post template selector dropdown.

		if ( 'wp_template_part' === $post->post_type ) {
			$type_terms = get_the_terms( $post, 'wp_template_part_area' );
			if ( ! is_wp_error( $type_terms ) && false !== $type_terms ) {
				$template->area = $type_terms[0]->name;
			}
		}

		// We are checking 'tripzzy' to maintain classic templates which are saved to the DB,
		// prior to updating to use the correct slug.
		if ( self::PLUGIN_SLUG === $theme ) {
			$template->origin = 'plugin';
		}

		return $template;
	}

	/**
	 * Converts template paths into a slug
	 *
	 * @param string $path The template's path.
	 * @since 1.0.6
	 * @return string slug
	 */
	public static function generate_template_slug_from_path( $path ) {
		$template_extension = '.html';

		return basename( $path, $template_extension );
	}

	/**
	 * Build a unified template object based on a theme file.
	 *
	 * @internal Important: This method is an almost identical duplicate from wp-includes/block-template-utils.php as it was not intended for public use. It has been modified to build templates from plugins rather than themes.
	 *
	 * @param array|object $template_file Theme file.
	 * @param string       $template_type wp_template or wp_template_part.
	 *
	 * @since 1.0.6
	 * @return \WP_Block_Template Template.
	 */
	public static function build_template_result_from_file( $template_file, $template_type ) {
		$template_file = (object) $template_file;

		// If the theme has an archive-tripzzy.html template but does not have tripzzy taxonomy templates
		// then we will load in the archive-tripzzy.html template from the theme to use for tripzzy taxonomies on the frontend.
		$template_is_from_theme = 'theme' === $template_file->source;
		$theme_name             = wp_get_theme()->get( 'TextDomain' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$template_content  = file_get_contents( $template_file->path );
		$template          = new \WP_Block_Template();
		$template->id      = $template_is_from_theme ? $theme_name . '//' . $template_file->slug : self::PLUGIN_SLUG . '//' . $template_file->slug;
		$template->theme   = $template_is_from_theme ? $theme_name : self::PLUGIN_SLUG;
		$template->content = self::inject_theme_attribute_in_content( $template_content );
		// Remove the term description block from the archive-tripzzy template.
		if ( 'archive-tripzzy' === $template_file->slug ) {
			$template->content = str_replace( '<!-- wp:term-description {"align":"wide"} /-->', '', $template->content );
		}
		// Plugin was agreed as a valid source value despite existing inline docs at the time of creating: https://github.com/WordPress/gutenberg/issues/36597#issuecomment-976232909.
		$template->source         = $template_file->source ? $template_file->source : 'plugin';
		$template->slug           = $template_file->slug;
		$template->type           = $template_type;
		$template->title          = ! empty( $template_file->title ) ? $template_file->title : self::get_block_template_title( $template_file->slug );
		$template->description    = ! empty( $template_file->description ) ? $template_file->description : self::get_block_template_description( $template_file->slug );
		$template->status         = 'publish';
		$template->has_theme_file = true;
		$template->origin         = $template_file->source;
		$template->is_custom      = false; // Templates loaded from the filesystem aren't custom, ones that have been edited and loaded from the DB are.
		$template->post_types     = array(); // Don't appear in any Edit Post template selector dropdown.
		$template->area           = 'uncategorized';

		// Force the Mini-Cart template part to be in the Mini-Cart template part area.
		// @todo When this class is refactored, move title, description, and area definition to the template classes (CheckoutHeaderTemplate, MiniCartTemplate, etc).
		if ( 'wp_template_part' === $template_type ) {
			switch ( $template_file->slug ) {
				case 'mini-cart':
					$template->area = 'mini-cart';
					break;
				case 'checkout-header':
					$template->area = 'header';
					break;
			}
		}
		return $template;
	}


	/**
	 * Build a new template object so that we can make Tripzzy Blocks default templates available in the current theme should they not have any.
	 *
	 * @param string $template_file Block template file path.
	 * @param string $template_type wp_template or wp_template_part.
	 * @param string $template_slug Block template slug e.g. single-tripzzy.
	 * @param bool   $template_is_from_theme If the block template file is being loaded from the current theme instead of Tripzzy Blocks.
	 *
	 * @since 1.0.6
	 * @return object Block template object.
	 */
	public static function create_new_block_template_object( $template_file, $template_type, $template_slug, $template_is_from_theme = false ) {
		$theme_name = wp_get_theme()->get( 'TextDomain' );

		$new_template_item = array(
			'slug'        => $template_slug,
			'id'          => $template_is_from_theme ? $theme_name . '//' . $template_slug : self::PLUGIN_SLUG . '//' . $template_slug,
			'path'        => $template_file,
			'type'        => $template_type,
			'theme'       => $template_is_from_theme ? $theme_name : self::PLUGIN_SLUG,
			// Plugin was agreed as a valid source value despite existing inline docs at the time of creating: https://github.com/WordPress/gutenberg/issues/36597#issuecomment-976232909.
			'source'      => $template_is_from_theme ? 'theme' : 'plugin',
			'title'       => self::get_block_template_title( $template_slug ),
			'description' => self::get_block_template_description( $template_slug ),
			'post_types'  => array(), // Don't appear in any Edit Post template selector dropdown.
		);
		return (object) $new_template_item;
	}

	/**
	 * Parses wp_template content and injects the current theme's
	 * stylesheet as a theme attribute into each wp_template_part
	 *
	 * @param string $template_content serialized wp_template content.
	 *
	 * @since 1.0.6
	 * @return string Updated wp_template content.
	 */
	public static function inject_theme_attribute_in_content( $template_content ) {
		$has_updated_content = false;
		$new_content         = '';
		$template_blocks     = parse_blocks( $template_content );

		$blocks = self::flatten_blocks( $template_blocks );
		foreach ( $blocks as &$block ) {
			if (
				'core/template-part' === $block['blockName'] &&
				! isset( $block['attrs']['theme'] )
			) {
				$block['attrs']['theme'] = wp_get_theme()->get_stylesheet();
				$has_updated_content     = true;
			}
		}

		if ( $has_updated_content ) {
			foreach ( $template_blocks as &$block ) {
				$new_content .= serialize_block( $block );
			}

			return $new_content;
		}

		return $template_content;
	}

	/**
	 * Returns an array containing the references of
	 * the passed blocks and their inner blocks.
	 *
	 * @param array $blocks array of blocks.
	 *
	 * @since 1.0.6
	 * @return array block references to the passed blocks and their inner blocks.
	 */
	public static function flatten_blocks( &$blocks ) {
		$all_blocks = array();
		$queue      = array();
		foreach ( $blocks as &$block ) {
			$queue[] = &$block;
		}
		$queue_count = count( $queue );

		while ( $queue_count > 0 ) {
			$block = &$queue[0];
			array_shift( $queue );
			$all_blocks[] = &$block;

			if ( ! empty( $block['innerBlocks'] ) ) {
				foreach ( $block['innerBlocks'] as &$inner_block ) {
					$queue[] = &$inner_block;
				}
			}

			$queue_count = count( $queue );
		}

		return $all_blocks;
	}

	/**
	 * Returns template titles.
	 *
	 * @param string $template_slug The templates slug (e.g. single-tripzzy).
	 * @since 1.0.6
	 * @return string Human friendly title.
	 */
	public static function get_block_template_title( $template_slug ) {
		$plugin_template_types = self::get_plugin_block_template_types();
		if ( isset( $plugin_template_types[ $template_slug ] ) ) {
			return $plugin_template_types[ $template_slug ]['title'];
		} else {
			// Human friendly title converted from the slug.
			return ucwords( preg_replace( '/[\-_]/', ' ', $template_slug ) );
		}
	}

	/**
	 * Returns template descriptions.
	 *
	 * @param string $template_slug The templates slug (e.g. single-tripzzy).
	 * @since 1.0.6
	 * @return string Template description.
	 */
	public static function get_block_template_description( $template_slug ) {
		$plugin_template_types = self::get_plugin_block_template_types();
		if ( isset( $plugin_template_types[ $template_slug ] ) ) {
			return $plugin_template_types[ $template_slug ]['description'];
		}
		return '';
	}

	/**
	 * Returns a filtered list of plugin template types, containing their
	 * localized titles and descriptions.
	 *
	 * @since 1.0.6
	 * @return array The plugin template types.
	 */
	public static function get_plugin_block_template_types() {
		return array(
			'single-tripzzy'  => array(
				'title'       => _x( 'Single Tripzzy', 'Template name', 'tripzzy' ),
				'description' => __( 'Displays a Trip Details.', 'tripzzy' ),
			),
			'archive-tripzzy' => array(
				'title'       => _x( 'Trips', 'Template name', 'tripzzy' ),
				'description' => __( 'Displays Trip archive.', 'tripzzy' ),
			),
		);
	}

	/**
	 * Gets the first matching template part within themes directories
	 *
	 * @param string $template_slug  The slug of the template (i.e. without the file extension).
	 * @param string $template_type  Either `wp_template` or `wp_template_part`.
	 *
	 * @since 1.0.6
	 * @return string|null  The matched path or `null` if no match was found.
	 */
	public static function get_theme_template_path( $template_slug, $template_type = 'wp_template' ) {
		$template_filename      = $template_slug . '.html';
		$possible_templates_dir = 'wp_template' === $template_type ? array(
			self::DIRECTORY_NAMES['TEMPLATES'],
			self::DIRECTORY_NAMES['DEPRECATED_TEMPLATES'],
		) : array(
			self::DIRECTORY_NAMES['TEMPLATE_PARTS'],
			self::DIRECTORY_NAMES['DEPRECATED_TEMPLATE_PARTS'],
		);

		// Combine the possible root directory names with either the template directory
		// or the stylesheet directory for child themes.
		$possible_paths = array_reduce(
			$possible_templates_dir,
			function ( $carry, $item ) use ( $template_filename ) {
				$filepath          = DIRECTORY_SEPARATOR . $item . DIRECTORY_SEPARATOR . $template_filename;
				$prefixed_filepath = DIRECTORY_SEPARATOR . $item . DIRECTORY_SEPARATOR . 'tz-' . $template_filename;
				$carry[]           = get_stylesheet_directory() . $prefixed_filepath;
				$carry[]           = get_template_directory() . $prefixed_filepath;

				$carry[] = get_stylesheet_directory() . $filepath;
				$carry[] = get_template_directory() . $filepath;

				return $carry;
			},
			array()
		);

		// Return the first matching.
		foreach ( $possible_paths as $path ) {
			if ( is_readable( $path ) ) {
				return $path;
			}
		}

		return null;
	}

	/**
	 * Check if the theme has a template. So we know if to load our own in or not.
	 *
	 * @param string $template_name name of the template file without .html extension e.g. 'single-tripzzy'.
	 *
	 * @since 1.0.6
	 * @return boolean
	 */
	public static function theme_has_template( $template_name ) {
		return ! ! self::get_theme_template_path( $template_name, 'wp_template' );
	}
}
