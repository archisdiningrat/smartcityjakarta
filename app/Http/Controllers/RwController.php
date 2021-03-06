<?php

namespace App\Http\Controllers;

use App\Models\Rw;
use Illuminate\Http\Request;
use GuzzleHttp;
use Maatwebsite\Excel\Facades\Excel;

class RwController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->client = new GuzzleHttp\Client(['headers' => ['Authorization' => config('api.key')]]);
        $this->url = config('api.url') . 'rw/';
    }

    public function index()
    {
        return view('rw.index', ['rw' => Rw::paginate(10)]);
    }

    public function remine()
    {
        $raw = $this->client->request('GET', $this->url);

        if ($raw->getStatusCode() != 200) {
            abort($raw->getStatusCode(), 'The response code is not 200');
        }

        $body = json_decode($raw->getBody(), true);

        foreach (Rw::all() as $item) {
            $item->delete();
        }

        foreach ($body['data'] as $item) {
            if(!Rw::find($item['kode_rw'])){
                Rw::create([
                    'id' => $item['kode_rw'],
                    'nama' => $item['nama_rw'],
                    'kota_id' => $item['kode_kota'],
                    'kecamatan_id' => $item['kode_kecamatan'],
                    'kelurahan_id' => $item['kode_kelurahan']
                ]);
            }
        }

        return redirect(route('rw.index'));
    }

    public function export(){

        $rw = Rw::all();
        $data = collect([]);

        foreach ($rw as $item){
            $data->push([
                'id' => $item->id,
                'nama' => $item->nama,
                'regional' => $item->kota->nama,
                'kecamatan' => $item->kecamatan->nama,
                'rw' => $item->kelurahan->nama
            ]);
        }

        Excel::create('Data RW', function ($excel) use ($data) {
            $excel->sheet('anggota', function ($sheet) use ($data) {
                $sheet->fromArray($data);
            })->export('xlsx');
        });

    }
}
