<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
   
    public function index(User $user)
    {

        // i user route model binding here because it mappe the id automatically to the model and return model resource  and handel the exeption also


        // here i just put the roles in the array and then check the rolles in array if present will return true
         $USER_ROLES = [
            env('ADMIN_ROLE_ID'),
            env('SUPERADMIN_ROLE_ID')
         ];

        //used here in_array function and auth helper function

        if(in_array(auth()->user()->user_type,$USER_ROLES))
        {
            $response = $this->repository->getAll($user);
            return response($response);
        }
        //use only one if condition 
         $response = $this->repository->getUsersJobs($user);


        return response($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show(Job $job)
    {
        $jobs = $job->with('translatorJobRel.user');

        return response($jobs);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $data = $request->all();

        //i use auth helper function 
        $response = $this->repository->store(auth()->user(), $data);

        return response($response);

    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update(Job $job, Request $request)
    {
        $jobs = $request->except(['_token', 'submit']);
        $cuser = auth()->user();
        $response = $this->repository->updateJob($job, $jobs, $cuser);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        // remove the unused peace of code 
        $jobs = $request->all();

        $response = $this->repository->storeJobEmail($jobs);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(User $user,Request $request)
    {

        // we dont have to use if condtion or return null because route model binding can do it automatically if it find no user it will automailly return findOrFail function
            $response = $this->repository->getUsersJobsHistory($user, $request);
            return response($response);
     
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $jobs = $request->all();
        $user = auth()->user();

        $response = $this->repository->acceptJob($jobs, $user);

        return response($response);
    }

    public function acceptJobWithId(Job $job)
    {
        $user = auth()->user();

        $response = $this->repository->acceptJobWithId($job, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        //use here the readable name instead of data
        $jobs = $request->all();
        
        $user = auth()->user();

        $response = $this->repository->cancelJobAjax($jobs, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $jobs = $request->all();

        $response = $this->repository->endJob($jobs);

        return response($response);

    }

    public function customerNotCall(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->customerNotCall($data);

        return response($response);

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->getPotentialJobs($user);

        return response($response);
    }

    public function distanceFeed(Request $request)
{
    $data = $request->all();
    $jobid = $data['jobid'];

    $distance = $data['distance'] ?? false;
    $time = $data['time'] ?? '';
    $session = $data['session_time'] ?? false;
    $admincomment = $data['admincomment'] ?? false;

    $flagged = ($data['flagged'] === 'true' && $data['admincomment'] !== '') ? true : false;
    $manually_handled = ($data['manually_handled'] === 'true') ? true : false;
    $by_admin = ($data['by_admin'] === 'true') ? true : false;

    if ($time || $distance) {
        Distance::where('job_id', $jobid)->update(['distance' => $distance, 'time' => $time]);
    }

    $distanceFeedArray = [
        $admincomment,
        $session,
        $flagged,
        $manually_handled,
        $by_admin,

    ];

    if (in_array(true, $distanceFeedArray)) {
        Job::where('id', $jobid)->update([
            'admin_comments' => $admincomment,
            'flagged' => $flagged,
            'session_time' => $session,
            'manually_handled' => $manually_handled,
            'by_admin' => $by_admin,
        ]);
    }

    return response('Record updated!');
}


    public function reopen(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->reopen($data);

        return response($response);
    }

    public function resendNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        // remove the unnecessary $job_data and use toArray() direclty in  sendNotificationTranslator
        $this->repository->sendNotificationTranslator($job, $job->toArray(), '*');

        return response(['success' => 'Push sent']);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $jobs = $request->all();
        $job = $this->repository->find($jobs['jobid']);

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }

}
