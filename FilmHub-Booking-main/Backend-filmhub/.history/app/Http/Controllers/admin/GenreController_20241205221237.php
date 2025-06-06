<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    const PATH_VIEW = 'admin.genres.';
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Genre::all();
        // dd($data);
        return view(self::PATH_VIEW . __FUNCTION__, compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view(self::PATH_VIEW . __FUNCTION__);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
        ]);
        Genre::query()->create([
            'name' => $request->name,

        ]);
        return redirect()->route('admin.genres.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $genre = Genre::find($id);
        return view('admin.genres.edit', compact('genre'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'name' => 'required',
        ]);
        $genre = Genre::find($id);
        $genre->update($data);
        return redirect()->route('admin.genres.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $genre = Genre::findOrFail($id);
        // Xóa phim
        $genre->delete();
        return redirect()->route('admin.genres.index')->with('success', 'Phim đã được xóa thành công!');
    }
}
