<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function unduh(Request $request): StreamedResponse
    {
        $dbName = DB::getDatabaseName();
        $filename = 'backup-' . preg_replace('/[^A-Za-z0-9_-]/', '', $dbName) . '-' . now()->format('Ymd-His') . '.sql';

        ActivityLog::catat('backup', 'pengaturan', "Backup database {$dbName} diunduh");

        return response()->streamDownload(function () {
            $pdo = DB::connection()->getPdo();

            $tables = collect(DB::select('SHOW FULL TABLES WHERE Table_type = ?', ['BASE TABLE']))
                ->map(fn ($r) => array_values((array) $r)[0]);

            echo "-- Backup database toko\n";
            echo '-- Dibuat: ' . now()->toDateTimeString() . "\n";
            echo "-- Aplikasi: KASIRA\n\n";
            echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                $create = DB::select("SHOW CREATE TABLE `{$table}`")[0];
                $createSql = $create->{'Create Table'};
                echo "-- ----------------------------------------\n";
                echo "-- Struktur `{$table}`\n";
                echo "-- ----------------------------------------\n";
                echo "DROP TABLE IF EXISTS `{$table}`;\n{$createSql};\n\n";

                $rows = DB::table($table)->orderBy(DB::raw(1))->get();
                if ($rows->isEmpty()) {
                    continue;
                }

                echo "-- Data `{$table}`\n";
                $columns = array_keys((array) $rows[0]);
                $colList = implode(',', array_map(fn ($c) => "`{$c}`", $columns));

                $chunks = $rows->chunk(200);
                foreach ($chunks as $chunk) {
                    $values = [];
                    foreach ($chunk as $row) {
                        $vals = [];
                        foreach ((array) $row as $v) {
                            $vals[] = is_null($v) ? 'NULL' : $pdo->quote((string) $v);
                        }
                        $values[] = '(' . implode(',', $vals) . ')';
                    }
                    echo "INSERT INTO `{$table}` ({$colList}) VALUES\n" . implode(",\n", $values) . ";\n";
                }
                echo "\n";
            }

            echo "SET FOREIGN_KEY_CHECKS=1;\n";
        }, $filename, ['Content-Type' => 'application/sql; charset=UTF-8']);
    }
}
