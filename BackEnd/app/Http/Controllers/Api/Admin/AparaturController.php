<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Aparatur;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\AparaturResource;
use Illuminate\Support\Facades\Validator;

class AparaturController extends Controller
{
    private $validRoles = [
        'BPH' => [
            'Ketua', 
            'Wakil Ketua',
            'Sekretaris', 
            'Wakil Sekretaris', 
            'Bendahara',
        ],
        'Pengembangan Seni & Kreativitas' => [
            'Pengembangan Seni & Kreativitas - Ketua',
            'Pengembangan Seni & Kreativitas - Sekretaris',
            'Pengembangan Seni & Kreativitas - Anggota',
        ],
        'Pengembangan SDM' => [
            'Pengembangan SDM - Ketua',
            'Pengembangan SDM - Sekretaris',
            'Pengembangan SDM - Anggota',
        ],
        'Ekonomi Kreatif' => [
            'Ekonomi Kreatif - Ketua',
            'Ekonomi Kreatif - Sekretaris',
            'Ekonomi Kreatif - Anggota',
        ],
        'Media Kreativitas & Informasi' => [
            'Media Kreativitas & Informasi - Ketua',
            'Media Kreativitas & Informasi - Sekretaris',
            'Media Kreativitas & Informasi - Anggota',
        ],
    ];

    private function getValidRolesFlat() {
        return array_merge(...array_values($this->validRoles));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //get aparaturs
        $aparaturs = Aparatur::when(request()->search, function ($aparaturs) {
            $aparaturs = $aparaturs->where('name', 'like', '%' . request()->search . '%');
        })->latest()->paginate(5);

        //append query string to pagination links
        $aparaturs->appends(['search' => request()->search]);

        //return with Api Resource
        return new AparaturResource(true, 'List Data Aparaturs', $aparaturs);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validRoles = $this->getValidRolesFlat();
    
        // Define roles that can have multiple entries
        $multipleEntriesRoles = [
            'Pengembangan Seni & Kreativitas - Anggota',
            'Pengembangan SDM - Anggota',
            'Ekonomi Kreatif - Anggota',
            'Media Kreativitas & Informasi - Anggota',
        ];
    
        // Validate input data
        $validator = Validator::make($request->all(), [
            'image'    => 'required|mimes:jpeg,jpg,png|max:2000',
            'name'     => 'required',
            'role'     => [
                'required',
                'in:' . implode(',', $validRoles),
                // Custom validation rule to ensure unique role, except for multiple entry roles
                function ($attribute, $value, $fail) use ($multipleEntriesRoles) {
                    if (!in_array($value, $multipleEntriesRoles)) {
                        $existingAparatur = Aparatur::where('role', $value)->first();
                        if ($existingAparatur) {
                            $fail($attribute . ' tersebut sudah ada');
                        }
                    }
                }
            ],
            'phone'    => 'required', // Validasi untuk phone
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
    
        // Upload image
        $image = $request->file('image');
        $image->storeAs('public/aparaturs', $image->hashName());
    
        // Create aparatur
        $aparatur = Aparatur::create([
            'image'     => $image->hashName(),
            'name'      => $request->name,
            'role'      => $request->role,
            'phone'     => $request->phone, // Simpan data phone
        ]);
    
        if ($aparatur) {
            // Return success with Api Resource
            return new AparaturResource(true, 'Data Anggota Berhasil Disimpan!', $aparatur);
        }
    
        // Return failed with Api Resource
        return new AparaturResource(false, 'Data Anggota Gagal Disimpan!', null);
    }
    

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $aparatur = Aparatur::whereId($id)->first();

        if ($aparatur) {
            //return success with Api Resource
            return new AparaturResource(true, 'Detail Data Anggota!', $aparatur);
        }

        //return failed with Api Resource
        return new AparaturResource(false, 'Detail Data Anggota Tidak Ditemukan!', null);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Aparatur $aparatur)
    {
        $validRoles = $this->getValidRolesFlat();
    
        // Define roles that can have multiple entries
        $multipleEntriesRoles = [
            'Pengembangan Seni & Kreativitas - Anggota',
            'Pengembangan SDM - Anggota',
            'Ekonomi Kreatif - Anggota',
            'Media Kreativitas & Informasi - Anggota',
        ];
    
        $validator = Validator::make($request->all(), [
            'name'     => 'required',
            'role'     => [
                'required',
                'in:' . implode(',', $validRoles),
                // Custom validation rule to ensure unique role, except for multiple entry roles
                function ($attribute, $value, $fail) use ($multipleEntriesRoles, $aparatur) {
                    if (!in_array($value, $multipleEntriesRoles)) {
                        $existingAparatur = Aparatur::where('role', $value)->where('id', '!=', $aparatur->id)->first();
                        if ($existingAparatur) {
                            $fail($attribute . ' tersebut sudah ada');
                        }
                    }
                }
            ],
            'phone'    => 'required', // Validasi untuk phone
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
    
        // Check image update
        if ($request->file('image')) {
            // Remove old image
            Storage::disk('local')->delete('public/aparaturs/' . basename($aparatur->image));
    
            // Upload new image
            $image = $request->file('image');
            $image->storeAs('public/aparaturs', $image->hashName());
    
            // Update aparatur with new image
            $aparatur->update([
                'image' => $image->hashName(),
                'name'  => $request->name,
                'role'  => $request->role,
                'phone' => $request->phone, // Simpan data phone
            ]);
        } else {
            // Update aparatur without image
            $aparatur->update([
                'name' => $request->name,
                'role' => $request->role,
                'phone' => $request->phone, // Simpan data phone
            ]);
        }
    
        if ($aparatur) {
            // Return success with Api Resource
            return new AparaturResource(true, 'Data Anggota Berhasil Diupdate!', $aparatur);
        }
    
        // Return failed with Api Resource
        return new AparaturResource(false, 'Data Anggota Gagal Diupdate!', null);
    }
    

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Aparatur $aparatur)
    {
        //remove image
        Storage::disk('local')->delete('public/aparaturs/' . basename($aparatur->image));

        if ($aparatur->delete()) {
            //return success with Api Resource
            return new AparaturResource(true, 'Data Anggota Berhasil Dihapus!', null);
        }

        //return failed with Api Resource
        return new AparaturResource(false, 'Data Anggota Gagal Dihapus!', null);
    }
}
