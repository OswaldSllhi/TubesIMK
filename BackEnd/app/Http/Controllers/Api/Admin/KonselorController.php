<?php

namespace App\Http\Controllers\Api\Admin;

    use App\Models\Konselor;
    use Illuminate\Http\Request;
    use App\Http\Controllers\Controller;
    use Illuminate\Support\Facades\Storage;
    use App\Http\Resources\KonselorResource;
    use Illuminate\Support\Facades\Validator;

    class KonselorController extends Controller
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
            // Get konselor
            $konselors = Konselor::when(request()->search, function ($query) {
                $query->where('name', 'like', '%' . request()->search . '%');
            })->latest()->paginate(5);

            // Append query string to pagination links
            $konselors->appends(['search' => request()->search]);

            // Return with API Resource
            return new KonselorResource(true, 'List Data konselor', $konselors);
        }

        public function store(Request $request)
        {
            $validRoles = $this->getValidRolesFlat();

            $validator = Validator::make($request->all(), [
                'image'    => 'required|mimes:jpeg,jpg,png|max:2000',
                'name'     => 'required',
                'role'     => 'required|in:' . implode(',', $validRoles),
                'phone'    => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            // Upload image
            $image = $request->file('image');
            $image->storeAs('public/konselor', $image->hashName());

            // Create Konselor
            $konselors = Konselor::create([
                'image'     => $image->hashName(),
                'name'      => $request->name,
                'role'      => $request->role,
                'phone'     => $request->phone,
            ]);

            // Return success with API Resource
            return new KonselorResource(true, 'Data Konselor Berhasil Disimpan!', $konselors);
        }

        /**
         * Display the specified resource.
         *
         * @param  int  $id
         * @return \Illuminate\Http\Response
         */

        public function show($id)
        {
            $konselors = Konselor::find($id);

            if ($konselors) {
                return new KonselorResource(true, 'Detail Data Konselor!', $konselors);
            }

            return new KonselorResource(false, 'Detail Data Konselor Tidak Ditemukan!', null);
        }

        /**
         * Update the specified resource in storage.
         *
         * @param  \Illuminate\Http\Request  $request
         * @param  int  $id
         * @return \Illuminate\Http\Response
         */

        public function update(Request $request, Konselor $konselor)
        {
            $validRoles = $this->getValidRolesFlat();
    
            $validator = Validator::make($request->all(), [
                'name'     => 'required',
                'role'     => 'required|in:' . implode(',', $validRoles),
                'phone'    => 'required', // Validasi untuk phone
            ]);
    
            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }
    
            //check image update
            if ($request->file('image')) {
                //remove old image
                Storage::disk('local')->delete('public/aparaturs/' . basename($konselor->image));
    
                //upload new image
                $image = $request->file('image');
                $image->storeAs('public/aparaturs', $image->hashName());
    
                //update aparatur with new image
                $konselor->update([
                    'image' => $image->hashName(),
                    'name'  => $request->name,
                    'role'  => $request->role,
                    'phone' => $request->phone, // Simpan data phone
                ]);
            } else {
                //update aparatur without image
                $konselor->update([
                    'name' => $request->name,
                    'role' => $request->role,
                    'phone' => $request->phone, // Simpan data phone
                ]);
            }
    
            if ($konselor) {
                //return success with Api Resource
                return new KonselorResource(true, 'Data Konselor Berhasil Diupdate!', $konselor);
            }
    
            //return failed with Api Resource
            return new KonselorResource(false, 'Data Konselor Gagal Diupdate!', null);
        }
    
        /**
         * Remove the specified resource from storage.
        *
        * @param  int  $id
        * @return \Illuminate\Http\Response
        */
        public function destroy(Konselor $konselor)
        {
            //remove image
            Storage::disk('local')->delete('public/aparaturs/' . basename($konselor->image));
    
            if ($konselor->delete()) {
                //return success with Api Resource
                return new KonselorResource(true, 'Data Konselor Berhasil Dihapus!', null);
            }
    
            //return failed with Api Resource
            return new KonselorResource(false, 'Data Konselor Gagal Dihapus!', null);
        }
    }
