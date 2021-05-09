<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file
 */

namespace MediaWiki\Extension\WikiSEO\Generator\Plugins;

use MediaWiki\Extension\WikiSEO\Generator\AbstractBaseGenerator;
use MediaWiki\Extension\WikiSEO\Generator\GeneratorInterface;
use MediaWiki\Extension\WikiSEO\WikiSEO;
use OutputPage;
use Title;

/**
 * The idea how it works is based on https://github.com/wikimedia/mediawiki-extensions-BreadCrumbs2
 */
class BreadCrumbs extends AbstractBaseGenerator implements GeneratorInterface {
	/**
	 * Valid Tags for this generator
	 *
	 * @var array
	 */
	protected $tags = [];

	private const TEMPLATE = '<script type="application/ld+json">%s</script>';
	private const DELIM = '>';

	/**
	 * Initialize the generator with all metadata and the page to output the metadata onto
	 *
	 * @param array $metadata All metadata
	 * @param OutputPage $out The page to add the metadata to
	 *
	 * @return void
	 */
	public function init( array $metadata, OutputPage $out ): void {
		$this->metadata = $metadata;
		$this->outputPage = $out;
	}

	/**
	 * Add the metadata to the OutputPage
	 *
	 * @return void
	 */
	public function addMetadata(): void {
		$categories = $this->outputPage->getCategories();
		if ( empty( $categories ) ) {
			return;
		}

		$breadcrumbTemplate = $this->findBreadCrumbsTemplate( $categories );
		$breadcrumbs = $this->parseBreadcrumbsTemplate( $breadcrumbTemplate );
		if ( empty( $breadcrumbs ) ) {
			return;
		}

		$listElements = [];
		foreach ( $breadcrumbs as $index => $data ) {
			$listElements[] = [
				'@type' => 'ListItem',
				'position' => $index + 1,
				'name' => $data["name"],
				'item' => $data["url"],
			];
		}

		$meta = [
			'@context' => 'http://schema.org',
			'@type' => 'BreadcrumbList',
			'itemListElement' => $listElements,
		];

		$this->outputPage->addHeadItem(
			'jsonld-metadata-breadcrumbs',
			sprintf( self::TEMPLATE, json_encode( $meta ) )
		);
	}

	public function getAllowedTagNames(): array {
		return $this->tags;
	}

	private function findBreadCrumbsTemplate( array $categories ): string {
		$breadCrumbsList = $this->loadBreadCrumbsList();
		foreach ( $breadCrumbsList as $category_key => $breadcrumbTemplate ) {
			$cat = trim( $category_key );
			if ( in_array( $cat, $categories ) ) {
				return $breadcrumbTemplate;
			}
		}
		return '';
	}

	private function loadBreadCrumbsList(): array {
		$msg = wfMessage( 'seo-breadcrumbs' );
		preg_match_all( '/^\s*\*\s*(.*)\s*@\s*(.*)$/m', $msg->plain(), $matches );
		return array_combine( $matches[1], $matches[2] );
	}

	private function parseBreadcrumbsTemplate( string $template ): array {
		$steps = explode( self::DELIM, $template );
		$breadcrumbs = [];
		foreach ( $steps as $step ) {
			$url = Title::newFromText( trim( $step ), NS_CATEGORY )->getFullUrl();
			$breadcrumbs[] = [
				"name" => trim( $step ),
				"url" => WikiSEO::protocolizeUrl( $url, $this->outputPage->getRequest() ),
			];
		}
		return $breadcrumbs;
	}
}
