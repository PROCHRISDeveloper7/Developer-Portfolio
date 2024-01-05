<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Jobs\SendNewPostEmail;
use Illuminate\Support\Facades\Mail;

class PostController extends Controller
{
    
    public function search($term) {
        $posts = Post::search($term)->get();
        $posts->load('user:id,username,avatar');
        return $posts;
    }

public function actuallyUpdate(Post $post, Request $request) {
    $incomingFields = $request->validate([
        'title' => 'required',
        'body' => 'required'
    ]);
    $incomingFields['title'] = strip_tags($incomingFields['title']);
    $incomingFields['body'] = strip_tags($incomingFields['body']);

    $post->update($incomingFields);

    return back()->with('success', 'Post updated!');

}

    public function showEditForm(Post $post) {
        return view('edit-post', ['post' => $post]);
    }


    public function delete(Post $post) {
if (auth()->user()->cannot('delete', $post)) {
    return 'You cant do that';
}
$post->delete();

return redirect('/profile/' . auth()->user()->username)->with('success', 'Post successfully deleted!');
    }

    public function  viewSinglePost(Post $post) {
        $post['body'] = Str::markdown($post->body);
        return view('single-post', ['post' => $post]);
    }

    // I used a custom name for this function instead of Brads storeNewPost name
    public function showNewPost(Request $request ) {
        $incomingFields = $request->validate([
            'title' => 'required',
            'body' => 'required'
        ]);

        $incomingFields['title'] = strip_tags($incomingFields['title']);
        $incomingFields['body'] = strip_tags($incomingFields['body']);
        $incomingFields['user_id'] = auth()->id();

$newPost = Post::create($incomingFields);

dispatch(new SendNewPostEmail(['sendTo' => auth()->user()->email, 'name'=> auth()->user()->username, 'title' => $newPost->title ]));

        return redirect("/post/{$newPost->id}")->with('success', 'New post sucessfully created!');
    }

    public function showCreateForm() {
        return view('create-post');
    }
}
