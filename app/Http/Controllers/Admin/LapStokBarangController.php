<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\BarangkeluarModel;
use App\Models\Admin\BarangmasukModel;
use App\Models\Admin\BarangModel;
use App\Models\Admin\WebModel;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use PDF;

class LapStokBarangController extends Controller
{
    public function index(Request $request)
    {
        $data["title"] = "Lap Stok Barang";
        return view('Admin.Laporan.StokBarang.index', $data);
    }

    public function print(Request $request)
    {
        $data['data'] = BarangModel::leftJoin('tbl_jenisbarang', 'tbl_jenisbarang.jenisbarang_id', '=', 'tbl_barang.jenisbarang_id')->leftJoin('tbl_satuan', 'tbl_satuan.satuan_id', '=', 'tbl_barang.satuan_id')->leftJoin('tbl_merk', 'tbl_merk.merk_id', '=', 'tbl_barang.merk_id')->orderBy('barang_id', 'DESC')->get();

        $data["title"] = "Print Stok Barang";
        $data['web'] = WebModel::first();
        $data['tglawal'] = $request->tglawal;
        $data['tglakhir'] = $request->tglakhir;
        return view('Admin.Laporan.StokBarang.print', $data);
    }

    public function pdf(Request $request)
    {
        $data['data'] = BarangModel::leftJoin('tbl_jenisbarang', 'tbl_jenisbarang.jenisbarang_id', '=', 'tbl_barang.jenisbarang_id')->leftJoin('tbl_satuan', 'tbl_satuan.satuan_id', '=', 'tbl_barang.satuan_id')->leftJoin('tbl_merk', 'tbl_merk.merk_id', '=', 'tbl_barang.merk_id')->orderBy('barang_id', 'DESC')->get();

        $data["title"] = "PDF Stok Barang";
        $data['web'] = WebModel::first();
        $data['tglawal'] = $request->tglawal;
        $data['tglakhir'] = $request->tglakhir;
        $pdf = PDF::loadView('Admin.Laporan.StokBarang.pdf', $data);
        
        if($request->tglawal){
            return $pdf->download('lap-stok-'.$request->tglawal.'-'.$request->tglakhir.'.pdf');
        }else{
            return $pdf->download('lap-stok-semua-tanggal.pdf');
        }
        
    }

    public function show(Request $request)
    {
    if ($request->ajax()) {
        $data = BarangModel::leftJoin('tbl_jenisbarang', 'tbl_jenisbarang.jenisbarang_id', '=', 'tbl_barang.jenisbarang_id')
            ->leftJoin('tbl_satuan', 'tbl_satuan.satuan_id', '=', 'tbl_barang.satuan_id')
            ->leftJoin('tbl_merk', 'tbl_merk.merk_id', '=', 'tbl_barang.merk_id')
            ->select('tbl_barang.*', 'tbl_jenisbarang.jenisbarang_nama', 'tbl_satuan.satuan_nama', 'tbl_merk.merk_nama')
            ->orderBy('barang_id', 'DESC')->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('stokawal', function ($row) {
                return '<span class="">' . $row->barang_stok . '</span>';
            })
            ->addColumn('jmlmasuk', function ($row) use ($request) {
                $jmlmasuk = BarangmasukModel::where('tbl_barangmasuk.barang_kode', $row->barang_kode)
                    ->when($request->tglawal, function ($query) use ($request) {
                        return $query->whereBetween('bm_tanggal', [$request->tglawal, $request->tglakhir]);
                    })->sum('tbl_barangmasuk.bm_jumlah');

                return '<span class="">' . $jmlmasuk . '</span>';
            })
            ->addColumn('jmlkeluar', function ($row) use ($request) {
                $jmlkeluar = BarangkeluarModel::where('tbl_barangkeluar.barang_kode', $row->barang_kode)
                    ->when($request->tglawal, function ($query) use ($request) {
                        return $query->whereBetween('bk_tanggal', [$request->tglawal, $request->tglakhir]);
                    })->sum('tbl_barangkeluar.bk_jumlah');

                return '<span class="">' . $jmlkeluar . '</span>';
            })
            ->addColumn('totalstok', function ($row) use ($request) {
                $jmlmasuk = BarangmasukModel::where('tbl_barangmasuk.barang_kode', $row->barang_kode)
                    ->when($request->tglawal, function ($query) use ($request) {
                        return $query->whereBetween('bm_tanggal', [$request->tglawal, $request->tglakhir]);
                    })->sum('tbl_barangmasuk.bm_jumlah');

                $jmlkeluar = BarangkeluarModel::where('tbl_barangkeluar.barang_kode', $row->barang_kode)
                    ->when($request->tglawal, function ($query) use ($request) {
                        return $query->whereBetween('bk_tanggal', [$request->tglawal, $request->tglakhir]);
                    })->sum('tbl_barangkeluar.bk_jumlah');

                $totalstok = $row->barang_stok + ($jmlmasuk - $jmlkeluar);

                if ($totalstok == 0) {
                    return '<span class="">' . $totalstok . '</span>';
                } elseif ($totalstok > 0) {
                    return '<span class="text-success">' . $totalstok . '</span>';
                } else {
                    return '<span class="text-danger">' . $totalstok . '</span>';
                }
            })
            ->rawColumns(['stokawal', 'jmlmasuk', 'jmlkeluar', 'totalstok'])
            ->make(true);
    }
    }
}
