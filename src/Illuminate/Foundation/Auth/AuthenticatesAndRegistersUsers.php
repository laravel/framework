<?php namespace Illuminate\Foundation\Auth;

use Auth;

use Illuminate\Http\Request;

trait AuthenticatesAndRegistersUsers {

    use AuthenticatesUsers;

    /**
     * Show the application registration form.
     *
     * @return \Illuminate\Http\Response
     */
    public function getRegister()
    {
        return view('auth.register');
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postRegister(Request $request)
    {
        $validator = $this->validator($request->all());

        if ($validator->fails())
        {
            $this->throwValidationException(
                $request, $validator
            );
        }

        Auth::login($this->create($request->all()));

        return redirect($this->redirectPath());
    }

    /**
     * Validates the submitted registration form
     *
     * @param array $data
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    abstract protected function validator(array $data);

    /**
     * Persist the registration form and returns it
     *
     * @param array $data
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    abstract protected function create(array $data);
}
