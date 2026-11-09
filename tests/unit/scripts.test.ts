/**
 * Unit tests for the build scripts' pure functions.
 */
import { describe, expect, it } from 'vitest';
import { checkSource, lookClasses } from '../../scripts/lib/boundaries.mjs';
import { rewrite } from '../../scripts/lib/prefix.mjs';
import { scopeSelector } from '../../scripts/lib/scoped-preflight.mjs';

describe( 'library boundary rule', () => {
	it( 'allows layout classes and flags look classes', () => {
		expect( lookClasses( 'mx-auto flex max-w-(--um-container) gap-4 md:grid text-center' ) ).toEqual( [] );
		expect( lookClasses( 'flex bg-primary md:text-lg hover:shadow-md rounded-md' ) ).toEqual( [ 'bg-primary', 'md:text-lg', 'hover:shadow-md', 'rounded-md' ] );
	} );

	it( 'ignores PHP expressions inside class attributes', () => {
		expect( lookClasses( '<?php echo $x; ?> flex' ) ).toEqual( [] );
	} );

	it( 'flags Flowbite outside packages/ui only', () => {
		const html = '<button data-modal-toggle="x" class="flex">';
		expect( checkSource( 'themes/ultra-max/x.php', html ) ).toHaveLength( 1 );
		expect( checkSource( 'packages/ui/components/x.php', html ) ).toHaveLength( 0 );
		expect( checkSource( 'plugins/a/x.ts', "import { Modal } from 'flowbite';" ).length ).toBeGreaterThan( 0 );
	} );

	it( 'flags direct shadcn and Radix imports', () => {
		expect( checkSource( 'plugins/a/app.tsx', "import { Button } from '@/components/ui/button';" ) ).toHaveLength( 1 );
		expect( checkSource( 'plugins/a/app.tsx', "import { Dialog } from 'radix-ui';" ) ).toHaveLength( 1 );
		expect( checkSource( 'plugins/a/app.tsx', "import { Button } from '@ultra-max/ui/admin';" ) ).toHaveLength( 0 );
	} );

	it( 'checks PHP array class props', () => {
		expect( checkSource( 'themes/ultra-max/t.php', "'class' => 'w-full bg-surface'," ) ).toHaveLength( 1 );
		expect( checkSource( 'themes/ultra-max/t.php', "'class' => 'w-full'," ) ).toHaveLength( 0 );
	} );
} );

describe( 'namespace prefixer', () => {
	it( 'rewrites shared namespaces and gettext domains', () => {
		const source = "namespace UltraMax\\UI;\nuse UltraMax\\Foundation\\Assets;\n__( 'Close', 'ultra-max-ui' );\nconst H = 'ultra-max-ui';";
		const out = rewrite( source, 'UltraMax\\Core', 'ultra-max-core' );
		expect( out ).toContain( 'namespace UltraMax\\Core\\Lib\\UI;' );
		expect( out ).toContain( 'use UltraMax\\Core\\Lib\\Foundation\\Assets;' );
		expect( out ).toContain( "__( 'Close', 'ultra-max-core' )" );
		expect( out ).toContain( "const H = 'ultra-max-ui';" );
	} );

	it( 'leaves product namespaces alone', () => {
		expect( rewrite( 'use UltraMax\\Core\\Lib\\UI\\UI;', 'UltraMax\\Core', 'ultra-max-core' ) ).toBe( 'use UltraMax\\Core\\Lib\\UI\\UI;' );
	} );
} );

describe( 'scoped preflight', () => {
	it( 'maps the root to the scope and scopes the rest', () => {
		expect( scopeSelector( 'html' ) ).toEqual( [ ':where(.um-scope)' ] );
		expect( scopeSelector( 'button' ) ).toEqual( [ ':where(.um-scope) button', 'button:where(.um-scope)' ] );
		expect( scopeSelector( '::placeholder' ) ).toEqual( [ ':where(.um-scope) ::placeholder' ] );
	} );
} );
