<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Periods;
use App\Models\Students;
use App\Models\Candidates;
use App\Models\Criteria;
use App\Models\PairwiseCriteria;
use App\Models\Weights;
use App\Models\Scores;
use App\Models\Results;
use App\Models\PairwiseAlternatives;
use App\Models\AuditLogs;

class TestSeederData extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Users
            $admin = User::firstOrCreate(
                ['email' => 'admin@example.com'],
                [
                    'name' => 'Admin',
                    'password' => Hash::make('Qwerty123*'),
                ]
            );

            // Period
            $period = Periods::create([
                'name' => 'Periode 2025',
                'status' => 'draft',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);

            // Students
            $stu1 = Students::create(['nis' => 'NIS001', 'name' => 'Budi', 'class' => 'X IPA 1']);
            $stu2 = Students::create(['nis' => 'NIS002', 'name' => 'Siti', 'class' => 'X IPS 1']);

            // Candidates (unique per (period, student))
            $cand1 = Candidates::create([
                'period_id' => $period->id,
                'student_id' => $stu1->id,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);
            $cand2 = Candidates::create([
                'period_id' => $period->id,
                'student_id' => $stu2->id,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);

            // Criteria: two root criteria (C1, C2) and one child under C1
            $c1 = Criteria::create([
                'period_id' => $period->id,
                'name' => 'Akademik',
                'type' => 'benefit',
                'order_index' => 0,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);
            $c2 = Criteria::create([
                'period_id' => $period->id,
                'name' => 'Ekstrakurikuler',
                'type' => 'benefit',
                'order_index' => 1,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);
            $c1a = Criteria::create([
                'period_id' => $period->id,
                'name' => 'Nilai Rapor',
                'type' => 'benefit',
                'parent_id' => $c1->id,
                'order_index' => 0,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);

            // Pairwise criteria between C1 and C2
            PairwiseCriteria::create([
                'period_id' => $period->id,
                'i_criterion_id' => $c1->id,
                'j_criterion_id' => $c2->id,
                'value' => 3.0000, // C1 moderately preferred over C2
                'updated_by' => $admin->id,
                'updated_at' => now(),
            ]);

            // Weights snapshot for nodes (example values)
            Weights::create([
                'period_id' => $period->id,
                'node_id' => $c1->id,
                'level' => 'criterion',
                'weight' => 0.60000000,
                'cr_at_level' => 0.05000000,
                'computed_at' => now(),
            ]);
            Weights::create([
                'period_id' => $period->id,
                'node_id' => $c2->id,
                'level' => 'criterion',
                'weight' => 0.40000000,
                'cr_at_level' => null,
                'computed_at' => now(),
            ]);
            // Subcriterion weight under C1 (if applicable)
            Weights::create([
                'period_id' => $period->id,
                'node_id' => $c1a->id,
                'level' => 'subcriterion',
                'weight' => 1.00000000, // local weight under C1
                'cr_at_level' => null,
                'computed_at' => now(),
            ]);

            // Scores for candidates per leaf criteria (use c1a and c2)
            $sc11 = Scores::create([
                'period_id' => $period->id,
                'criterion_id' => $c1a->id,
                'candidate_id' => $cand1->id,
                'raw_value' => 88.2500,
                'normalized_value' => 0.95,
                'evidence_url' => null,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);
            $sc12 = Scores::create([
                'period_id' => $period->id,
                'criterion_id' => $c2->id,
                'candidate_id' => $cand1->id,
                'raw_value' => 8.0000,
                'normalized_value' => 0.80,
                'evidence_url' => null,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);
            $sc21 = Scores::create([
                'period_id' => $period->id,
                'criterion_id' => $c1a->id,
                'candidate_id' => $cand2->id,
                'raw_value' => 82.5000,
                'normalized_value' => 0.89,
                'evidence_url' => null,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);
            $sc22 = Scores::create([
                'period_id' => $period->id,
                'criterion_id' => $c2->id,
                'candidate_id' => $cand2->id,
                'raw_value' => 9.0000,
                'normalized_value' => 0.90,
                'evidence_url' => null,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);

            // Results (example final scores respecting uniques)
            $res1 = Results::create([
                'period_id' => $period->id,
                'candidate_id' => $cand1->id,
                'final_score' => 0.89,
                'rank' => 2,
                'computed_at' => now(),
            ]);
            $res2 = Results::create([
                'period_id' => $period->id,
                'candidate_id' => $cand2->id,
                'final_score' => 0.92,
                'rank' => 1,
                'computed_at' => now(),
            ]);

            // Pairwise alternatives for criterion C2: cand2 preferred over cand1
            PairwiseAlternatives::create([
                'period_id' => $period->id,
                'criterion_id' => $c2->id,
                'i_candidate_id' => $cand2->id,
                'j_candidate_id' => $cand1->id,
                'value' => 3.0000,
                'updated_by' => $admin->id,
                'updated_at' => now(),
            ]);

            // Audit logs
            AuditLogs::create([
                'period_id' => $period->id,
                'entity' => 'criteria',
                'entity_id' => $c1a->id,
                'action' => 'create',
                'changes_json' => ['name' => [null, 'Nilai Rapor']],
                'acted_by' => $admin->id,
                'acted_at' => now(),
            ]);
        });
    }
}
