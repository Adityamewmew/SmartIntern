import 'dotenv/config';
import { superAdminSeeder } from './superAdminSeeder';
import { sidebarSeeder } from './sidebarSeeder';
import { holidaySeeder } from './holidaySeeder';

const seeders: Array<{ name: string; fn: () => Promise<void> }> = [
    { name: 'superAdminSeeder', fn: superAdminSeeder },
    { name: 'sidebarSeeder', fn: sidebarSeeder },
    { name: 'holidaySeeder', fn: holidaySeeder },
];

async function runSeeders(): Promise<void> {
    console.log('Starting seeders...\n');

    for (const seeder of seeders) {
        console.log(`Running: ${seeder.name}`);
        await seeder.fn();
    }

    console.log('\nAll seeders completed.');
    process.exit(0);
}

runSeeders().catch((err) => {
    console.error('Seeder failed:', err);
    process.exit(1);
});
