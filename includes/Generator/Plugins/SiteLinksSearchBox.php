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

class SiteLinksSearchBox extends AbstractBaseGenerator implements GeneratorInterface {
	/**
	 * Valid Tags for this generator
	 *
	 * @var array
	 */
	protected $tags = [
		'type',
		'searchbox',
	];

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
		$addSearchBox = false;
		foreach ( $this->tags as $tag ) {
			if ( array_key_exists( $tag, $this->metadata ) && $tag === "searchbox" ) {
				$addSearchBox = true;
			}
		}
		if ( !$addSearchBox ) {
			return;
		}

		$template = '<script type="application/ld+json">%s</script>';

		$meta = [
			'@context' => 'http://schema.org',
			'@type' => $this->getTypeMetadata(),
		];

		if ( $this->outputPage->getTitle() !== null ) {
			$url = $this->outputPage->getTitle()->getFullURL();

			$url = WikiSEO::protocolizeUrl( $url, $this->outputPage->getRequest() );

			$meta['identifier'] = $url;
			$meta['url'] = $url;
		}

		$meta['potentialAction'] = $this->getSearchActionMetadata();

		$this->outputPage->addHeadItem(
			'jsonld-metadata',
			sprintf( $template, json_encode( $meta ) )
		);
	}

	public function getAllowedTagNames(): array {
		return $this->tags;
	}

	private function getTypeMetadata(): string {
		return $this->metadata['type'] ?? 'article';
	}

	private function getSearchActionMetadata(): array {
		$searchPage = Title::newFromText( 'Special:Search' );

		if ( $searchPage !== null ) {
			$search =
				$searchPage->getFullURL( [ 'search' => 'search_term' ], false,
					sprintf( '%s://', $this->outputPage->getRequest()->getProtocol() ) );
			$search = str_replace( 'search_term', '{search_term}', $search );

			return [
				'@type' => 'SearchAction',
				'target' => $search,
				'query-input' => 'required name=search_term',
			];
		}

		return [];
	}
}
