/**
 * Admin catalog: every admin component, rendered with WordPress's React.
 */
import { createRoot } from 'react-dom/client';
import { useState } from 'react';
import { AdminRoot, Button, Dialog, Switch, Tabs, TextField } from '@ultra-max/ui/admin';

function Catalog() {
	const [ open, setOpen ] = useState( false );
	return (
		<AdminRoot>
			<div className="grid max-w-3xl gap-8 py-4">
				<section data-testid="admin-button" className="flex flex-wrap gap-3">
					<Button>ذخیره</Button>
					<Button variant="outline">پیش‌نمایش</Button>
					<Button variant="soft">کپی</Button>
					<Button variant="ghost">انصراف</Button>
					<Button variant="danger">حذف</Button>
					<Button size="sm">کوچک</Button>
					<Button disabled>غیرفعال</Button>
				</section>
				<section data-testid="admin-switch" className="grid gap-3">
					<Switch id="module-sms" label="ماژول ورود پیامکی" defaultChecked />
					<Switch id="module-invoice" label="ماژول فاکتور" />
				</section>
				<section data-testid="admin-textfield">
					<TextField id="store-name" label="نام فروشگاه" help="در سربرگ و فاکتور نمایش داده می‌شود." defaultValue="الترا مکس" />
				</section>
				<section data-testid="admin-tabs">
					<Tabs
						label="تنظیمات"
						items={ [
							{ value: 'general', label: 'عمومی', content: 'تنظیمات عمومی فروشگاه.' },
							{ value: 'colors', label: 'رنگ‌ها', content: 'رنگ اصلی و پیش‌تنظیم‌ها.' },
							{ value: 'fonts', label: 'فونت‌ها', content: 'فونت متن و تیترها.' },
						] }
					/>
				</section>
				<section data-testid="admin-dialog">
					<Dialog
						open={ open }
						onOpenChange={ setOpen }
						trigger="بازنشانی تنظیمات"
						title="بازنشانی همه تنظیمات؟"
						description="همه تنظیمات قالب به حالت اول برمی‌گردد."
						footer={
							<>
								<Button variant="ghost" onClick={ () => setOpen( false ) }>انصراف</Button>
								<Button variant="danger" onClick={ () => setOpen( false ) }>بازنشانی</Button>
							</>
						}
					>
						<p>این کار برگشت‌پذیر نیست.</p>
					</Dialog>
				</section>
			</div>
		</AdminRoot>
	);
}

const mount = document.getElementById( 'um-admin-catalog' );
if ( mount ) {
	createRoot( mount ).render( <Catalog /> );
}