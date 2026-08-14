			Usage: Instantiate the onboarding as follows:

	add_action( 'plugins_loaded', [ $this, 'setup_onboarding' ] );

	public function setup_onboarding(): void {
		$onboarding = new Onboarding();
		if ( $onboarding::is_onboarding_active( 'burst' ) ) {
			$onboarding->init();
		}
	}

	When a user activated the plugin, or you want to start the onboarding process, you set the optioin update_option( "{$prefix}_start_onboarding", true)
	This option is deleted when the scripts are enqueued, so the onboarding will only run once.
	It might be a good idea to use a transient for any redirect, so in case a plugin is automatically installed, the transient expires after a few minutes.



	Usually you would want to add a redirect to the settings page as well.

			Onboarding_steps should return an array of steps, each step should be an array with the following keys:
			[
				'id'       => 'intro',
				'title'    => __( 'Take a minute to configure Burst!', 'burst-statistics' ),
				'subtitle' => __( "In a few steps we'll help you select the best options, and verify if statistics have started tracking.", 'burst-statistics' ),
				'button'   => [
					'id'    => 'start',
					'label' => __( 'Start onboarding', 'burst-statistics' ),
				],
			],

			The button key can also have an action key. The action key should match an endpoint.