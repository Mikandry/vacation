<?php
namespace App\Http\Controllers;

use App\Services\IssueService;

class IssueController extends Controller
{
    public function show(string $key, IssueService $svc)
    {
        if ($key === 'chevauchement') return redirect()->route('conflicts.index');
        $issue = $svc->get($key);
        abort_if($issue === null, 404);
        return view('issues.show', ['issue' => $issue, 'key' => $key]);
    }
}
