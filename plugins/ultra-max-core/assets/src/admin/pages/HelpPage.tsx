/**
 * Help (ADM-06): the Persian help articles inside the dashboard, without
 * outside links. The same files make help.pdf.
 */
import { useMemo, useState } from 'react';
import { Card, PageHeader, RichText, SearchBox, SideNav, Text } from '@ultra-max/ui/admin';
import { boot, t } from '../boot';
import { normalize } from '../settings/SettingsPage';

export function HelpPage() {
	const articles = boot.help ?? [];
	const fromHash = () => {
		const hash = window.location.hash.slice( 1 );
		return articles.some( ( article ) => article.id === hash ) ? hash : articles[ 0 ]?.id ?? '';
	};
	const [ current, setCurrent ] = useState( fromHash );
	const article = articles.find( ( item ) => item.id === current );
	const index = useMemo( () => articles.map( ( item ) => ( { id: item.id, title: item.title, text: normalize( item.title + ' ' + item.html.replace( /<[^>]+>/g, ' ' ) ) } ) ), [ articles ] );

	const open = ( id: string ) => {
		setCurrent( id );
		window.history.replaceState( null, '', `#${ id }` );
		window.scrollTo( { top: 0 } );
	};

	return (
		<div className="mx-auto grid max-w-310 gap-4">
			<PageHeader title={ t( 'helpTitle' ) } subtitle={ t( 'helpSubtitle' ) }>
				<SearchBox
					label={ t( 'helpSearch' ) }
					placeholder={ t( 'helpSearch' ) }
					empty={ t( 'searchEmpty' ) }
					search={ ( query ) => {
						const words = normalize( query ).split( ' ' );
						return index.filter( ( item ) => words.every( ( word ) => item.text.includes( word ) ) ).slice( 0, 8 ).map( ( item ) => ( { id: item.id, label: item.title, context: t( 'helpTitle' ) } ) );
					} }
					onPick={ open }
				/>
			</PageHeader>
			<div className="grid items-start gap-4 xl:grid-cols-[260px_minmax(0,1fr)]">
				<SideNav label={ t( 'helpArticles' ) } current={ current } onSelect={ open } items={ articles.map( ( item ) => ( { id: item.id, label: item.title, icon: 'file' } ) ) } />
				<Card>
					{ article ? (
						// The HTML comes from our own Markdown, built at release time and passed through wp_kses_post().
						<RichText html={ article.html } />
					) : (
						<Text tone="faint">{ t( 'helpEmpty' ) }</Text>
					) }
				</Card>
			</div>
		</div>
	);
}
