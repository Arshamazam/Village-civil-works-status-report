<?php

namespace Database\Seeders;

use App\Models\Reason;
use Illuminate\Database\Seeder;

class ReasonsSeeder extends Seeder
{
    public function run(): void
    {
        // Top level Contractor
        $c = Reason::updateOrCreate(
            ['code' => 'C'],
            [
                'label' => 'Contractor Issues',
                'top_level_group' => 'Contractor',
                'parent_id' => null,
                'requires_remark' => false,
                'sort_order' => 10,
            ]
        );

        Reason::updateOrCreate(
            ['code' => 'C1'],
            [
                'label' => 'Labour / machinery not deployed, no valid reason',
                'top_level_group' => 'Contractor',
                'parent_id' => $c->id,
                'requires_remark' => false,
                'sort_order' => 11,
            ]
        );

        Reason::updateOrCreate(
            ['code' => 'C2'],
            [
                'label' => 'Material not arranged at site',
                'top_level_group' => 'Contractor',
                'parent_id' => $c->id,
                'requires_remark' => false,
                'sort_order' => 12,
            ]
        );

        Reason::updateOrCreate(
            ['code' => 'C3'],
            [
                'label' => 'Own cash-flow or sub-contractor dispute',
                'top_level_group' => 'Contractor',
                'parent_id' => $c->id,
                'requires_remark' => false,
                'sort_order' => 13,
            ]
        );

        // Top level PRMSC
        $p = Reason::updateOrCreate(
            ['code' => 'P'],
            [
                'label' => 'PRMSC / Department Issues',
                'top_level_group' => 'PRMSC',
                'parent_id' => null,
                'requires_remark' => false,
                'sort_order' => 20,
            ]
        );

        Reason::updateOrCreate(
            ['code' => 'P1'],
            [
                'label' => 'Payment / IPC pending with PRMSC',
                'top_level_group' => 'PRMSC',
                'parent_id' => $p->id,
                'requires_remark' => false,
                'sort_order' => 21,
            ]
        );

        Reason::updateOrCreate(
            ['code' => 'P2'],
            [
                'label' => 'Extension of Time (EOT) pending',
                'top_level_group' => 'PRMSC',
                'parent_id' => $p->id,
                'requires_remark' => false,
                'sort_order' => 22,
            ]
        );

        Reason::updateOrCreate(
            ['code' => 'P3'],
            [
                'label' => 'Design, drawing or decision pending (PRMSC / consultant)',
                'top_level_group' => 'PRMSC',
                'parent_id' => $p->id,
                'requires_remark' => false,
                'sort_order' => 23,
            ]
        );

        // Top level Govt. Land
        $l = Reason::updateOrCreate(
            ['code' => 'L'],
            [
                'label' => 'Govt. Land / Right of Way',
                'top_level_group' => 'Govt. Land',
                'parent_id' => null,
                'requires_remark' => false,
                'sort_order' => 30,
            ]
        );

        Reason::updateOrCreate(
            ['code' => 'L1'],
            [
                'label' => 'Site possession, land or right-of-way issue',
                'top_level_group' => 'Govt. Land',
                'parent_id' => $l->id,
                'requires_remark' => false,
                'sort_order' => 31,
            ]
        );

        // Top level External
        $x = Reason::updateOrCreate(
            ['code' => 'X'],
            [
                'label' => 'External / Environmental Factors',
                'top_level_group' => 'External',
                'parent_id' => null,
                'requires_remark' => false,
                'sort_order' => 40,
            ]
        );

        Reason::updateOrCreate(
            ['code' => 'X1'],
            [
                'label' => 'Weather, flooding or water-logging',
                'top_level_group' => 'External',
                'parent_id' => $x->id,
                'requires_remark' => false,
                'sort_order' => 41,
            ]
        );

        Reason::updateOrCreate(
            ['code' => 'X2'],
            [
                'label' => 'Community dispute, law & order or local event',
                'top_level_group' => 'External',
                'parent_id' => $x->id,
                'requires_remark' => false,
                'sort_order' => 42,
            ]
        );

        Reason::updateOrCreate(
            ['code' => 'X3'],
            [
                'label' => 'NOC from departments pending',
                'top_level_group' => 'External',
                'parent_id' => $x->id,
                'requires_remark' => false,
                'sort_order' => 43,
            ]
        );

        // Top level VLD's
        $v = Reason::updateOrCreate(
            ['code' => 'V'],
            [
                'label' => "VLD's (Village-level Issues)",
                'top_level_group' => "VLD's",
                'parent_id' => null,
                'requires_remark' => false,
                'sort_order' => 50,
            ]
        );

        Reason::updateOrCreate(
            ['code' => 'V1'],
            [
                'label' => 'Village-level dispute or local committee decision pending',
                'top_level_group' => "VLD's",
                'parent_id' => $v->id,
                'requires_remark' => false,
                'sort_order' => 51,
            ]
        );

        // Other
        Reason::updateOrCreate(
            ['code' => 'OT'],
            [
                'label' => 'Other (explain in Remarks)',
                'top_level_group' => 'Other',
                'parent_id' => null,
                'requires_remark' => true,
                'sort_order' => 99,
            ]
        );
    }
}
