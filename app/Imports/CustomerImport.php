<?php

namespace App\Imports;

use App\Models\Login;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CustomerImport implements ToModel,WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Login([
            'name' => $row['name'],
            'password' => $row['password'],
        ]);
    }
}
