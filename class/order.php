<?php

/**
 * order
 */
class order
{

	private $con;
	private $customer_id;

	/**
	 * __construct
	 *
	 * @param  mixed $name
	 * @return void
	 */
	function __construct($name)
	{
		$this->customer_id = $_SESSION['customer_id']; // Thsi is depends on how we treat custoomer, I assume the user is loggedin user (not guest)

		$this->con = mysqli_connect("localhost", "my_user", "my_password", "my_db");

		if (mysqli_connect_errno()) {
			echo "Failed to connect to MySQL: " . mysqli_connect_error();
			exit();
		}
	}


	/**
	 * addReward
	 * Descripption: 1. Customers will be rewarded with Points when Sales Order in “Complete” status.	
	 * @param  object $order
	 * @return void
	 */
	function addReward(object $order)
	{
		if ('COMPLETE' === strtoupper($order->status)) {
			$price = $order->price;
			$currency = $order->currency;
			if ($currency != 'USD') {
				$price = $this->convertCurrency($currency, 'USD', $price);
			}
			$reward_points = $price;
			$reward_date = date('Y-m-d H:i:s');
			$reward_expiry_date = date('Y-m-d', strtotime('+1 year')) . '23:59:59';
			$reward_status = 'credit';  // or debit
			$insert_query = "INSERT INTO customer_rewards (customer_id, reward_point , reward_status, reward_date , reward_expiry_date) VALUES ($this->customer_id,  $reward_points, $reward_status, $reward_date , $reward_expiry_date)";
			mysqli_query($this->con, $insert_query);

			// I assume , reward_points field is at customer table
			$update_query = "UPDATE customer SET reward_points = reward_points + $reward_points  WHERE customer_id = '$this->customer_id'";
			mysqli_query($this->con, $update_query);

			// I assumre cron will run to remove expired rewards daily.

		} else {
			return;
		}
	}


	/**
	 * convertCurrency
	 *
	 * @param  string $currency_from
	 * @param  string $currency_to
	 * @param  float $currency_input
	 * @return float
	 */
	function convertCurrency(string $currency_from, string $currency_to, float $currency_input): float
	{
		$yql_base_url = "http://query.yahooapis.com/v1/public/yql";
		$pair = $currency_from . $currency_to;
		$query = 'select * from yahoo.finance.xchange where pair in ("' . $pair . '")';
		$query_url = $yql_base_url . "?q=" . urlencode($query) . "&format=json&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys";
		
		$curl = curl_init($query_url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($curl);
		curl_close($curl);
		
		$json_data = json_decode($response, true);
		$rate = $json_data['query']['results']['rate']['Rate'];
		
		return (float) $currency_input * $rate;
	}


	/**
	 * redeemRewardPoint
	 *
	 * @param  object $order
	 * @return float
	 */
	function redeemRewardPoint(object $order)
	{
		$price = $order->price;
		$reward_points = $this->getRewardPoint();
		$total_redemption_value = $reward_points * 0.01;

		$price_after_redemption = $price - $total_redemption_value;
		if ($price_after_redemption < 0) {
			$price_after_redemption = 0;
		}
		$reward_points = $price;
		$reward_date = date('Y-m-d H:i:s');
		$reward_expiry_date = date('Y-m-d H:i:s');
		$reward_status = 'debit';  // or credit
		$insert_query = "INSERT INTO customer_rewards (customer_id, reward_point , reward_status, reward_date , reward_expiry_date) VALUES ($this->customer_id,  $reward_points, $reward_status, $reward_date , $reward_expiry_date)";
		mysqli_query($this->con, $insert_query);

		$update_query = "UPDATE customer SET reward_points = reward_points - $reward_points  WHERE customer_id = '$this->customer_id'";
		mysqli_query($this->con, $update_query);

		return (float) $price_after_redemption;
	}

	/**
	 * getRewardPoint
	 *
	 * @return int
	 */
	function getRewardPoint()
	{
		$get_reward = "SELECT reward_points from customer where customer_id = '$this->customer_id'";
		$result = mysqli_query($this->con, $get_reward);
		if ($result->num_rows > 0) {
			return $result->fetch_row()[0]['reward_points'] ?? 0;
		} else {
			return 0;
		}
	}
}
