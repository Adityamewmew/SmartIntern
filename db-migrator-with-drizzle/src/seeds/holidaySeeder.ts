import db from '../db';
import { holidaysTable } from '../db/schema';
import { eq } from 'drizzle-orm';

export async function holidaySeeder(): Promise<void> {
    const holidays = [
        { holiday_date: '2026-01-01', holiday_name: 'Tahun Baru 2026 Masehi', is_national_holiday: 1 },
        { holiday_date: '2026-02-17', holiday_name: 'Isra Mikraj Nabi Muhammad SAW', is_national_holiday: 1 },
        { holiday_date: '2026-02-19', holiday_name: 'Tahun Baru Imlek 2577 Kongzili', is_national_holiday: 1 },
        { holiday_date: '2026-03-20', holiday_name: 'Hari Suci Nyepi Tahun Baru Saka 1948', is_national_holiday: 1 },
        { holiday_date: '2026-03-21', holiday_name: 'Hari Raya Idul Fitri 1447 Hijriah', is_national_holiday: 1 },
        { holiday_date: '2026-04-03', holiday_name: 'Wafat Yesus Kristus', is_national_holiday: 1 },
        { holiday_date: '2026-05-01', holiday_name: 'Hari Buruh Internasional', is_national_holiday: 1 },
        { holiday_date: '2026-05-14', holiday_name: 'Kenaikan Yesus Kristus', is_national_holiday: 1 },
        { holiday_date: '2026-05-27', holiday_name: 'Hari Raya Idul Adha 1447 Hijriah', is_national_holiday: 1 },
        { holiday_date: '2026-05-31', holiday_name: 'Hari Raya Waisak 2570 BE', is_national_holiday: 1 },
        { holiday_date: '2026-06-01', holiday_name: 'Hari Lahir Pancasila', is_national_holiday: 1 },
        { holiday_date: '2026-06-17', holiday_name: 'Tahun Baru Islam 1448 Hijriah', is_national_holiday: 1 },
        { holiday_date: '2026-08-17', holiday_name: 'Hari Kemerdekaan Republik Indonesia', is_national_holiday: 1 },
        { holiday_date: '2026-08-26', holiday_name: 'Maulid Nabi Muhammad SAW', is_national_holiday: 1 },
        { holiday_date: '2026-12-25', holiday_name: 'Hari Raya Natal', is_national_holiday: 1 },
    ];

    for (const h of holidays) {
        // check if exists
        const existing = await db
            .select()
            .from(holidaysTable)
            .where(eq(holidaysTable.holiday_date, new Date(h.holiday_date)))
            .limit(1);

        if (existing.length === 0) {
            await db.insert(holidaysTable).values({
                holiday_date: new Date(h.holiday_date),
                holiday_name: h.holiday_name,
                is_national_holiday: h.is_national_holiday,
                created_at: new Date(),
                updated_at: new Date(),
            });
            console.log(`Inserted holiday: ${h.holiday_name}`);
        } else {
            console.log(`Holiday ${h.holiday_name} already exists`);
        }
    }
}
