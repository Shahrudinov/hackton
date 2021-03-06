<?php

namespace App\Http\Controllers;

use App\Author;
use App\Book;
use App\BookCategory;
use App\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BooksController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $sort = $request->get('sort');

        switch ($sort) {
            case 'date':
                $books = Book::orderBy('created_at', 'desc')->get();
                break;
            case 'category':
                $books = BookCategory::where('id', $request->category)->first()->books;
                break;
            case 'stock':
                $books = Book::where('count', '>', 0)->orderBy('created_at', 'desc')->get();
                break;
            case 'author':
                $books = Author::where('id', $request->author)->first()->books;
                break;
            default:
                $books = Book::orderBy('created_at', 'desc')->get();
        }

        return view('books.index', compact('books'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Book $book)
    {
        try {
            DB::transaction(function () use ($book, $request) {
                $book->requests()->create([
                    'user_id' => Auth::user()->id,
                    'book_id' => $book->id,
                    'count' => $request->count,
                    'completed' => false,
                    'return_date' => $request->return_date ?? now(),
                    'comments' => $request->comment,
                ]);
            });
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param  Book $book
     * @return \Illuminate\Http\Response
     */
    public function show(Book $book)
    {
        return view('books.show', ['book' => $book]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function storeReview(Request $request, Book $book)
    {
        Review::create([
            'stars' => $request->stars ?? 0,
            'comment' => $request->comment,
            'book_id' => $book->id,
            'user_id' => Auth::user()->id,
        ]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Book $book
     * @return \Illuminate\Http\Response
     */
    public function destroy(Book $book)
    {
        Auth::user()->requests()->where([
            'book_id' => $book->id,
        ])->delete();
        return back();
    }
}
